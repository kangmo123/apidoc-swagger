数据模型

该目录下的文件都是用来定义数据模型与数据库之间的关系，以及数据模型之间的关系(例如1对1，1对n，m对n等)。

建议数据的读取操作尽量使用Repo方式实现，方便之后应对存储介质的变化，这里的Model既作为DB操作模型又作为纯数据模型由Repo返回。

模型定义方式请参考[此链接](https://laravel.com/docs/5.5/eloquent)

关系定义方式请参考[此链接](https://laravel.com/docs/5.5/eloquent-relationships)