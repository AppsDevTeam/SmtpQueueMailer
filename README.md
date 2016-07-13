ADT/SmtpQueueMailer
======


Registrace extension
---
```
extensions:
    queueMailer: ADT\SmtpQueueMailer\DI\SmtpQueueMailerExtension
```

Nastavení extension:
---
```
queueMailer:
	path: '%appDir%/model/mailer.db'
	options:
		lazy: true
```

Registrace maileru:
---
**Nový mailer:** (možnost využít předpřipravenou třídu `ADT\SmtpQueueMailer\Mailer`)
```
services:
	nette.mailer:
		class: ADT\SmtpQueueMailer\Mailer(%mailer%)
		tags: [queueMailer]
```
 + přidání nastavení pro mailer:
```
	parameters:
		mailer:
			smtp: true
			host:
			port:
			username:
			password:
```
**Úprava existujícího maileru** (vlastní třída)

Přidat traitu `use \ADT\SmtpQueueMailer\SmtpQueueMailerTrait;`

např.:
```
class Mailer extends Nette\Mail\SmtpMailer {
	use \ADT\SmtpQueueMailer\SmtpQueueMailerTrait;
}
```

pro přepsání metody maileru `send` je třeba mailer upravit:
```
class Mailer extends \ADT\Mail\SingleRecipientMailer {
	use SmtpQueueMailer {
		send as queueSend;
	}

	public function send(\Nette\Mail\Message $message) {
		…
		$this->queueSend($mail); // místo parent::send($mail)
	}

}
```

Command pro opětovné odeslání emailů z fronty:
---
`php www/index.php adt:queueMailerResend`