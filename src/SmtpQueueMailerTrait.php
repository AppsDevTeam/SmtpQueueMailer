<?php

namespace ADT\SmtpQueueMailer;

trait SmtpQueueMailerTrait {

	/** @var Service\MessageQueue */
	protected $mailerService;

	/** @var NULL|integer */
	protected $messageId = NULL;

	/**
	 * @param \ADT\SmtpQueueMailer\Service\MessageQueue $mailerService
	 */
	public function injectQueueService(\ADT\SmtpQueueMailer\Service\MessageQueue $mailerService) {
		$this->mailerService = $mailerService;
	}

	/**
	 * @param Nette\Mail\Message $mail
	 * @param integer|NULL $messageId ID zprávy z databáze (pokud se jedná o zprávu z DB)
	 */
	public function send(\Nette\Mail\Message $mail) {

		try {
			parent::send($mail);

			if ($this->messageId) {
				$this->mailerService->deleteMessage($this->messageId);
			}
		} catch (\Exception $e) {
			// zalogování chyby
			\Tracy\Debugger::log($e, \Tracy\ILogger::EXCEPTION);

			// uložení emailu do DB pro pozdější odeslání
			$this->mailerService->saveMessage($mail, $this->messageId);
		} finally {
			$this->messageId = NULL;
		}
	}

	/**
	 * Znovu odešle všechny zprávy z DB
	 */
	public function resendMessages() {
		foreach ($this->mailerService->getMessagesQueue() as $message) {
			$this->messageId = $message["id"];
			$this->send(unserialize($message["message"]));
		}
	}

}
