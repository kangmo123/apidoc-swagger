输出数据转换器

该目录下的文件是输出数据转换器的定义，它是为了适配Model层和输出JSON数据以及处理各种输出格式和级联操作准备的。
它的输入是要返回的单个Model、Model的集合、或Paginator，它的输出是准备返回出去的API的JSON结构。

它的作用：

    1. 将Model层的数据准备成API上规定的JSON的样子
    2. 之后可以针对不同的输出格式例如YAML, XML等做转换
    3. 可以处理特殊的格式要求，例如是否要外面套一层data结构，是否要统一输出page_info等信息
    4. 可以处理要求进行级联加载的资源，解决N+1调用问题。

示例：
```
class UserResource extends Resource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}
```

如何定义数据转换器，请参考[此链接](https://laravel.com/docs/5.5/eloquent-resources#concept-overview).