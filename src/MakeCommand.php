<?php
// +----------------------------------------------------------------------
// | 种菜郎服务端
// +----------------------------------------------------------------------
// | User: wzdon
// +----------------------------------------------------------------------
// | Author: king east <1207877378@qq.com>
// +----------------------------------------------------------------------

namespace ke\model;


use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\Db;
use think\facade\App;
use think\facade\Config;
use function Symfony\Component\String\u;

class MakeCommand extends Command
{

    public function configure()
    {
        $this->setName('ke:model:make')
            ->addOption('table', 't', Option::VALUE_OPTIONAL, '表名')
            ->addOption('namespace', 'name', Option::VALUE_OPTIONAL, '命名空间')
            ->addOption('dir', 'd', Option::VALUE_OPTIONAL, '输出目录')
        ;
    }


    public function execute(Input $input, Output $output)
    {
        $table = $input->getOption('table');
        $pre = Config::get('database.prefix');
        $database = Config::get('database.database');
        $mutil_module = Config::get('app_multi_module');

        $model_dir = ($input->getOption('dir') ?? 'common');
        $output_dir = App::getAppPath() . ($mutil_module ? $model_dir . '/' : '') . 'model/';

        if (!is_dir($output_dir)) {
            mkdir($output_dir, 0555, true);
        }

        if (!is_dir($output_dir)) {
            mkdir($output_dir, 0555, true);
        }

        $content = file_get_contents(__DIR__ . '/stub/model.stub');

        $res = Db::query(sprintf("SELECT column_name,column_comment,data_type FROM information_schema.columns WHERE table_name='%s' AND table_schema='%s'", $pre . $table, $database));

        $propertys = [];
        $types = '';
        $timestamp = [
            'create'=>false,
            'update'=>false,
        ];
        foreach ($res as $item) {
            if ($item['column_name'] === 'create_time') {
                $timestamp['create'] = true;
                continue;
            }

            if ($item['column_name'] === 'update_time') {
                $timestamp['update'] = true;
                continue;
            }
            $data_type = $item['data_type'];

            $type = null;
            if (strpos($data_type, 'char') !== false || strpos($data_type, 'text') !== false || strpos($data_type, 'blob') !== false) {
                $data_type = 'string';
            } else if (strpos($data_type, 'int') !== false) {
                $data_type = 'int';
                $type = 'integer';
            } else if ($data_type === 'decimal' || $data_type === 'double') {
                $data_type = 'float';
                $type = 'float';
            } else if (in_array($data_type, ['date', 'time', 'year', 'datetime', 'timestamp'])) {
                $data_type = 'string';
            } else if ($data_type === 'json') {
                $data_type = 'array';
                $type = 'json';
            }

            $propertys[] = " * @property {$data_type} \${$item['column_name']} {$item['column_comment']}";
            if ($type) {
                $types .= sprintf("        '%s'=>'%s',", $item['column_name'], $type) . PHP_EOL;
            }
        }
        $comment = implode("\r\n", $propertys);

        $classname = u($table)->camel()->title();
        if (Config::get('app.class_suffix')) {
            $classname .= 'Model';
        }

        $namespace = $input->getOption('namespace');


        if (!is_dir($output_dir . $namespace)) {
            mkdir($output_dir . $namespace, 0555, true);
        }
        $file = $output_dir . $namespace . '/' . $classname . '.php';

        if (is_file($file)) {
            $output->writeln('文件' . $file . '已存在!');
            return;
        }

        $timestampStr = '';
        if ($timestamp['create'] || $timestamp['update']) {
            $timestampStr .= '    protected $autoWriteTimestamp = true;' . PHP_EOL;
        }
        if ($timestamp['create']) {
            $timestampStr .= '    protected $createTime = \'create_time\';' . PHP_EOL;
        } else {
            $timestampStr .= '    protected $createTime = false;' . PHP_EOL;
        }
        if ($timestamp['update']) {
            $timestampStr .= '    protected $updateTime = \'update_time\';' . PHP_EOL;
        } else {
            $timestampStr .= '    protected $updateTime = false;' . PHP_EOL;
        }

        $ns = ['app'];
        if ($mutil_module) {
            $ns[] = str_replace('/', '\\', ($model_dir ? $model_dir . '/' : ''));
        }
        $ns[] = 'model';
        if ($namespace) {
            $ns[] = $namespace;
        }

        $content = str_replace([
            '{%namespace%}',
            '{%className%}',
            '{%propertys%}',
            '{%types%}',
            '{%timestamp%}'
        ], [
            implode('\\', $ns),
            $classname,
            $comment,
            $types,
            $timestampStr
        ], $content);

        file_put_contents($file, $content);

        $output->writeln('write:' . $file);
        $output->writeln('make model success!');
    }

}
