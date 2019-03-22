中间件过滤器

该目录下的文件都是用来定义中间件的，中间件的目的是在Request到达Controller前或者在Response到达客户前执行一些操作。

常见操作有：

    1. 权限验证
    2. 身份检查
    3. 请求限流
    4. 输出压缩
    5. 输出刷新缓存
    
如何使用中间件，请参考[此链接](https://laravel.com/docs/5.5/middleware)