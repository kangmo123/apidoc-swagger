贮存器定义

该目录下定义的为数据贮存器，它是Model层上层的一个设施，负责取得Model数据模型。
它的输入为各种查询数据的Filter对象，它输出为查询出来的数据Model，该Model可能从DB ES API Redis构建。

它的目的：

    1. 将数据读取的逻辑单独出来，供Controller或者Service层调用。
    2. Controller和Service层不需要关心具体存储是使用DB, ES, Redis甚至是远程API获取的。
    3. 由于微服务开始时边界划分可能会出现问题，使用Repo模式可以避免某实体被划到另外服务后对上层造成的巨大改动。
    4. 由于数据层默认DB查询，在性能出现问题后可能会采用ES或Redis进行加速，使用Repo模式可以避免切换后上层巨大改动。
    
一般的Repo结构，下面以User获取查询为例
```
/Repositories
    /User
        /UserRepository.php      //该文件是一个Interface，定义了查询用户的各种方法，接收UserFilter作为查询条件
        /UserDBRepository.php    //该文件是Interface的DB实现，定义了可以如何从DB查询并构建用户
        /UserESRepository.php    //该文件是Interface的ES实现，定义了可以如何从ES查询并构建用户
        /UserRedisRepository.php //该文件是Interface的Redis实现，定义了可以如何从Redis查询并构建用户
        /UserAPIRepository.php   //该文件是Interface的API实现，定义了可以如何从API查询并构建用户
        /UserFilter.php          //该文件是查询条件的集合，一般由Request参数构建该对象，处理分页排序参数的逻辑
```
PS：一般情况下只对接口进行一个存储介质的实现，在需要替换存储介质的时候再实现另外的。

Controller在需要查询某个数据的时候通过依赖注入UserRepository接口来依靠接口进行操作。
```
class UserController
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
        return UserResource::item($user);
    }
}
```
需要替换具体实现的时候在RepositoryServiceProvider里面修改依赖注入的绑定关系即可。
```
    $this->app->bind(UserRepository::class, UserDBRepository::class);//修改前
    $this->app->bind(UserRepository::class, UserAPIRepository::class);//修改后
```
如果同时需要两个Repo在不同情况下工作，比如大部分情况下都是用Cache的实现，有几个地方需要用DB的实现，则可以用以下方式：
```
    $this->app->bind(UserRepository::class, UserCacheRepository::class);//处理大部分情况
    $this->app->when(UserController::class)     //当UserController依赖注入
              ->needs(UserRepository::class)    //需要UserRepository的时候
              ->give(UserDBRepository::class);  //我给他的是UserDBRepository的实现。

```

PPS：并不建议所有的存取都使用Repo模式，因为大部分还都是读取本微服务的DB数据，但是以下几种情况建议早做预防。

    1. 查询需求很多的数据实体，比如客户或者品牌，各种繁杂的查询会导致DB无法满足，之后会考虑换ES实现。
    2. 对于性能要求很高的实体，比如城市、地区，开始用DB实现没什么问题，之后可能要加缓存，就要考虑Redis实现。
    3. 对于之后是否还属于本微服务的实体不太确定，可能会被划分出去，那要及早防范，考虑之后用API实现。