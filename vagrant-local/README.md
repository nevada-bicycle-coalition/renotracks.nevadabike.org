# Vagrant Local App Server #

With [Vagrant](http://vagrantup.com) installed, launch RenoTracks on a local server with

```
$ vagrant up
```

Run that in the same directory as this file.

We have an issue where varnish doesn't install on the first try, sigh. Reprovision:

```
$ vagrant provision
```

You can get a shell on the VM:

```
$ vagrant ssh
```

Add an entry to your [hosts file][1] to reach the web server

```
192.168.56.101  renotracks.dev
```

[1]:http://www.howtogeek.com/howto/27350/beginner-geek-how-to-edit-your-hosts-file/

Now http://renotracks.dev should serve up the app. 

If you get a 502 Gateway error, it's probably this issue:

http://stackoverflow.com/questions/23443398/nginx-error-connect-to-php5-fpm-sock-failed-13-permission-denied

When finished

```
$ vagrant halt
```

will stop the server until next time.

