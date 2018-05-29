# 单服务框架

## 说明（特色、局限性）

* 这个框架是用来实现内部接口服务的，不是做网站的（不考虑SEO，视图、丰富路由协议等）
* http协议，如果使用rawdata提交参数，只支持json格式
* 返回值固定json格式，需自行修改代码支持其他情况（没预留）
* 配置获取支持两种方式：本地路径或远程ini服务。
* 每个服务器都内置了个ServiceName用于运维管理，比如启动停止服务，比如重新获取刷新配置等。该ServiceName名为SteadyAsHill（虽然占用，但限制了只能127.0.0.1访问）。


## 名词解释

* uri中  /servicename/controllername/actionname, 每个servicename对应启动一个Server
* 关于模块名和服务名：物理磁盘路径中的名字是模块名，url中的名字是服务名，服务名可以在配置文件中更改，且可以多个（比如由于版本升级，需要servicename有所区别，但又需要兼顾旧版一段时间）

## 参看

[编写hello world](docs/Codes.md)

[框架代码说明](docs/Frameworkd.md)

[运维管理说明](docs/Maintain.md)

## 其他

**关于库文件加载，基本分三种情况：**

1. 模块专属类库（library目录），在run.php的入口文件通过设置->initAutloadLocalLibrary(true)，可以在启动时全部预先加载，此时就不需要相应的autoload函数了
2. 对于遵循psr-4规范的类库目录，可以在run.php的入口开头加入相关autoload，或通过server->initIncludePath()指定尝试搜索目录（仅支持一个目录）
3. 其他像composer这种外部类库，在run.php的入口开头加入相关autoload

**ServiceSample 下是几个小的Service骨架**

能用，但不一定合适你的情况能直接用，都是些很简单示例性的小服务

使用前修改：

1. 合并所有_config目录下的配置
2. 确认或修改bin/run.php 里 SingleService/Server.php 的路径
3. 确认或修改bin/run.php 里 initServiceModule 那里模块路径
4. 确认或修改bin/start.sh 里 配置文件获取方式

* Email 发送邮件的
* HelloWorld 演示测试用的
* Ini 配置管理


