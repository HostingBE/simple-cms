<?php

namespace App\Console\Commands;

use PDO; 
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
 
class ReminderEmail extends Command {

    protected $db;
    protected $settings;
    protected $logger;
    protected $mail;
    protected $view;
    protected $onderwerp = 'We do miss you on SiteName, how are you?';

     public function __construct($db, $settings, $logger, $mail, $view) {
        
        parent::__construct();
        
        $this->db = $db;
        $this->settings = $settings;
        $this->logger = $logger;    
        $this->mail = $mail;
        $this->view = $view; 
        }

        protected function configure() {
        $this->setName('reminder-email')
            ->setDescription('stuurt een email om de 30, 60 en 90 dagen naar niet ingelogde gebruikers!')
            ->addArgument('cmd', InputArgument::REQUIRED, 'Welk commando wil je laten uitvoeren?')
            ->setHelp('Nog geen help beschikbaar voor dit commando.');
        }
   

    protected function execute(InputInterface $input, OutputInterface $output)   {
    $cmd  =  $input->getArgument('cmd');

    $this->logger->warning("ik ga het commando " . $cmd . " uitvoeren!");
    
    if ($cmd == "send-reminders") {
    if ($this->send_reminders($input, $output) === 0) {
        return 0;
        }
    return 1;
    }


    if ($cmd == "activate-reminder") {
    if ($this->activate_reminder($input, $output) === 0) {
        return 0;
        }
    return 1;
    }


    }


    protected function activate_reminder(InputInterface $input, OutputInterface $output) {


    $sql = $this->db->prepare("SELECT a.first_name,a.last_name,a.email,b.user_id,b.code,b.id FROM users a, activations b WHERE b.user_id=a.id AND b.completed='0' AND DATEDIFF(now(),a.created_at) < 8 ORDER BY b.id DESC");
    $sql->execute();
    $activations = $sql->fetchALL(PDO::FETCH_OBJ);
    
    foreach ($activations as $activation) {

    $code = random(32);
    $email_hash = hash('sha256', $activation->email);
    $this->setSubject('Your account is one step from beeing activated!');

    $mailbody = $this->view->fetch('email/reminder-activation.twig',['url' => $this->settings['url'], 'activation' => $activation,'subject' => $this->getSubject(),'email_hash'=> $email_hash,'code'=> $code,'footer' => $this->settings['footer']]);
    // herinnering sturen naar bezoeker
    $this->mail->setFrom($this->settings['email'],$this->settings['email_name']);
    $this->mail->addAddress($activation->email, $activation->first_name . " " . $activation->last_name);
    $this->mail->addBCC($this->settings['emailto'],$this->settings['emailto_name']);
    $this->mail->Subject = $this->getSubject();
    $this->mail->Body = $mailbody;      
    $this->mail->isHTML(true);

    /*
    * e-mail die verstuurd wordt in de datbase stoppen
    */
    $sql = $this->db->prepare("INSERT INTO email (id,code,onderwerp,email,user,body,datum) VALUES('',:code,:onderwerp,:email,:user,:body,now())");
    $sql->bindparam(":code",$code,PDO::PARAM_STR);
    $sql->bindparam(":onderwerp",$this->getSubject(), PDO::PARAM_STR);
    $sql->bindparam(":email",$email_hash,PDO::PARAM_STR);
    $sql->bindparam(":user",$activation->id,PDO::PARAM_INT);
    $sql->bindparam(":body",$mailbody,PDO::PARAM_STR);
    $sql->execute();


    if(!$this->mail->send()) {
                $this->logger->warning('Activation reminder e-mail send to ' . $activation->email . " is " . $this->mail->ErrorInfo);
                } else {
                $this->logger->warning('Activtion reminder of total days is  ' . $activation->aantaldagen . ' sent to e-mail address ' . $activation->email);
                }  
    $this->mail->clearAllRecipients(); // clear all



            }
    }


    protected function send_reminders(InputInterface $input, OutputInterface $output)    {

    $sql = $this->db->prepare("SELECT id,first_name,last_name,email,DATEDIFF(CURRENT_DATE(),last_login) as aantaldagen FROM users HAVING aantaldagen IN ('0','5','60','90')");
    $sql->execute();
    $reminders = $sql->fetchALL(PDO::FETCH_OBJ);


    /*
    * Ophalen 3 blogs voor de email als advertentie
    */
    $sql = $this->db->prepare("SELECT id,title,SUBSTR(content,1,100) AS content,image FROM blog ORDER BY id DESC LIMIT 3");
    $sql->execute();
    $blogs = $sql->fetchALL(PDO::FETCH_OBJ);

    foreach ($reminders as $reminder) {
     
    $code = random(32);
    $email_hash = hash('sha256', $reminder->email);

    $mailbody = $this->view->fetch('email/reminder-email.twig',['url' => $this->settings['url'], 'reminder' => $reminder,'subject' => $this->subject, 'blogs' => $blogs,'email_hash'=> $email_hash,'code'=> $code,'footer' => $this->settings['footer']]);
    // herinnering sturen naar bezoeker
    $this->mail->setFrom($this->settings['email'],$this->settings['email_name']);
    $this->mail->addAddress($reminder->email, $reminder->first_name . " " . $reminder->last_name);
    $this->mail->addBCC($this->settings['emailto'],$this->settings['emailto_name']);
    $this->mail->Subject = $this->onderwerp;
    $this->mail->Body = $mailbody;      
    $this->mail->isHTML($this->settings['html_email']);

    /*
    * e-mail die verstuurd wordt in de datbase stoppen
    */
    $sql = $this->db->prepare("INSERT INTO email (id,code,onderwerp,email,user,body,datum) VALUES('',:code,:onderwerp,:email,:user,:body,now())");
    $sql->bindparam(":code",$code,PDO::PARAM_STR);
    $sql->bindparam(":onderwerp",$this->onderwerp,PDO::PARAM_STR);
    $sql->bindparam(":email",$email_hash,PDO::PARAM_STR);
    $sql->bindparam(":user",$reminder->id,PDO::PARAM_INT);
    $sql->bindparam(":body",$mailbody,PDO::PARAM_STR);
    $sql->execute();


    if(!$this->mail->send()) {
                $this->logger->warning('Reminder e-mail send to ' . $reminder->email . " is " . $this->mail->ErrorInfo);
                } else {
                $this->logger->warning('Reminder of total days is  ' . $reminder->aantaldagen . ' sent to e-mail address ' . $reminder->email);
                }  
    $this->mail->clearAllRecipients(); // clear all
    }
     return 0;
    }
private function setSubject($subject) {
    $this->subject = $subject;
    }
private function getSubject() {
    return $this->subject;
    }    
}