<?php

namespace ADT\SmtpQueueMailer;

class Mailer extends \Nette\Mail\SmtpMailer {

	use SmtpQueueMailerTrait;
}
