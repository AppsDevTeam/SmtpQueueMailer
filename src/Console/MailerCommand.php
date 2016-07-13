<?php

namespace ADT\SmtpQueueMailer\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 */
class MailerCommand extends Command {

	/** @var \Nette\Mail\IMailer */
	protected $mailer;

	protected function configure() {
		$this->setName('adt:queueMailerResend');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 */
	protected function initialize(InputInterface $input, OutputInterface $output) {
		$this->mailer = $this->getHelper('container')->getByType(\Nette\Mail\IMailer::class);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->mailer->resendMessages();

		echo "SUCCESS \n";
	}

}
