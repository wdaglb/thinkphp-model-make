# thinkphp-model-make
thinkphp model生成器

安装

```
composer require ke/thinkphp-model-make
```

使用
```
php think ke:model:make --table=project
```

参数说明

参数|必须|说明
---|---|---
table|是|表名,无需前缀,会自动引入database的前缀
namespace|否|命名空间;只需输入子空间名
dir|否|输出目录；默认为common
