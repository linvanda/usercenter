[基础框架使用说明](https://gitlab4.weicheche.cn/framework/wecarswoole)

### 服务命令(都是在项目根目录执行)：
- 启动：`sudo -u www php easyswoole start -d --env=test`
其中 --env ：测试环境：test，预发布：preview，生产：produce
- 停止：`sudo -u www php easyswoole stop`
有时异常情况下,storage/temp/ 目录没有pid文件，无法用上面的命令停止，则执行以下命令：
`netstat -anp|grep 8087` 拿到 pid
`kill -15 $pid` kill 掉。（注意不要用 kill -9，该强制 kill 会造成僵尸进程）
- 重启：`sudo -u www php easyswoole reload all`
注意：必须用 www 用户操作，否则可能会报权限问题（实际服务启动成功了，但写入 pid 失败，此次无法正常停止）。