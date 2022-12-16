<?php
    namespace App\Controllers;
    
    use App\Exceptions\CustomException;
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    require __DIR__.'/../../vendor/phpmailer/src/Exception.php';
    require __DIR__.'/../../vendor/phpmailer/src/PHPMailer.php';
    require __DIR__.'/../../vendor/phpmailer/src/SMTP.php';

    class MailController
    {
        protected $email;
        protected $subject;
        protected $message;
        
        public function __construct(string $email, string $subject, string $message)
        {
            $this->email = $email;
            $this->subject = $subject;
            $this->message = $message;
            $this->sendMail();
        }

        private function sendMail():void
        {
            $mail = new PHPMailer(true);
            try {                
                $mail->isSMTP();                                           
                $mail->Host       = MAILSENDHOST;
                $mail->SMTPAuth   = true;
                $mail->Username   = MAILSENDADDRESS;
                $mail->Password   = MAILSENDPASSWORD;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port       = MAILSENDPORT;
            
                $mail->setFrom(MAILSENDADDRESS, MAILSENDADDRESS);
                $mail->addAddress($this->email, $this->email);
                $mail->addReplyTo(MAILSENDADDRESS, MAILSENDADDRESS);
                
                $mail->isHTML(true);                                 
                $mail->Subject = $this->subject;
                $mail->Body    = $this->message;
                $this->message = strip_tags($this->message);
                $mail->AltBody = $this->message;
            
                $mail->send();
                
            } catch (Exception $e) {
                throw new CustomException('MAIL_SEND_ERROR', 0, $e->getMessage());
            }
        }
    }
?>