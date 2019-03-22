输入数据填充器

该目录下的文件是数据填充器的定义，数据填充器是为了解决API过来JSON结构的校验以及适配Model层。
它的输入是API过来的JSON和要创建或更新的数据模型，它的输出是创建或更新好的数据模型。

它的作用：

    1. 通过ValidationRules来检验传递过来的JSON是否合法，不合法自动报错
    2. 将合法的JSON字段赋值到Model上去，完成Model的创建和更新，在此过程适配字段名称不一致问题。
    3. 将插入和更新数据的功能封装在一个地方可以很好的自动开启、提交、回滚事务，如果过程中扔出异常则事务回滚。
    
示例：
```
class UserHydrator extends Hydrator
{
    protected function getCreateRules()
    {
        return [
            'name' => 'required|max:20',
            'email' => 'required|email',
        ];
    }

    protected function getUpdateRules()
    {
        return [
            'name' => 'max:20',
            'email' => 'email',
        ];
    }

    protected function hydrateForCreate(array $data, Model $model)
    {
        $model->name = $data['name'];
        $model->email = $data['email'];
        $model->save();
        return $model;
    }

    protected function hydrateForUpdate(array $data, Model $model)
    {
        if (isset($data['name'])) {
            $model->name = $data['name'];
        }
        if (isset($data['email'])) {
            $model->email = $data['email'];
        }
        $model->save();
        return $model;
    }
}
```
如何定义JSON校验规则，请参考[此链接](https://laravel.com/docs/5.5/validation#available-validation-rules)