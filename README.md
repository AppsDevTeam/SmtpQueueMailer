ADT/SmtpQueueMailer
======

`config.neon`:
```
services:
	nette.mailer:
			class: ADT\SmtpQueueMailer\Mailer(%mailer%)

parameters:
	mailer:
		smtp: true
		host: xxx
		port: xxx
		username: xxx
		password: xxx
		sqlite:
			path: '%appDir%/model/mailer.db'
			options:
				lazy: true

```