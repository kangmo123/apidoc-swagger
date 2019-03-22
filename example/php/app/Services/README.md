业务服务

该目录下的文件都是业务服务的定义，他是真正业务逻辑实现的地方，做到和平台框架无关。

常见的业务服务例如用户服务，其中包含注册用户的功能，注册用户会涉及到很多方面的业务操作，因此要单独封装统一的方法供外部调用。

```
class UserService
{
    public function register($data)
    {
        $this->checkIfCanCreateUser($data);
        $user = User::create($data);
        trggerEvent(UserWasCreated($user));
        dispatchJob(SendWelcomeEmailToUser($user));
        return $user;
    }
}

```
类似于以上处理一系列业务逻辑的功能，为了防止每次都重复在其他地方重写，建议提成Service服务供统一调用。