控制器定义

该目录下的文件用来定义MVC架构中的控制器，正常情况下不应该会有太多行代码，真正的逻辑应该封装在底层实现。
它的输入为Request，它的输出为Response，负责调用Service, Repository或直接调用Model来实现接口逻辑。

示例：
```
class UserController extends Controller
{
    public function index(Request $request, UserRepostiroy $userRepo)
    {
        $filter = new UserFilter($request);
        $paginator = $userRepo->getPaginatorByFilter($filter);
        return UserResource::collection($paginator);
    }
    
    public function show($userId, UserRepository $userRepo)
    {
        $user = $userRepo->findById($userId);
        if (!$user) {
            throw new NotFound();
        }
        $this->authorize('view', $user);
        return UserResource::item($user);
    }
    
    public function store()
    {
        $this->authorize('create', User::class);
        $user = $this->hydrate(new User, new UserHydrator);
        return UserResource::item($user)->response()->setStatusCode(201);
    }

    public function update($userId)
    {
        $user = User::findOrFail($userId);
        $this->authorize('update', $user);
        $user = $this->hydrate($user, new UserHydrator);
        return UserResource::item($user);
    }
    
    public function delete($userId)
    {
        $user = User::find($userId);
        if ($user && $this->authorize('delete', $user)) {
            $user->delete();
        }
        return response()->setStatusCode(204);
    }
}
```

如何使用控制器，请参考[此链接](https://laravel.com/docs/5.5/controllers)