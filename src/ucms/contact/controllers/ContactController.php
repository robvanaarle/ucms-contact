<?php

namespace ucms\contact\controllers;

class ContactController extends \ultimo\mvc\Controller {
  
  protected $config;
  
  protected function init() {
    $this->config = $this->module->getPlugin('config')->getConfig('general');
  }
  
  protected function getFormName() {
    $formName = $this->request->getParam('form_name');
    if ($formName === null) {
      throw new \ultimo\mvc\exceptions\DispatchException("Missing form_name.", 404);
    }
    
    if (!isset($this->config['forms'][$formName]) || !is_array($this->config['forms'][$formName])) {
      throw new \ultimo\mvc\exceptions\DispatchException("Missing config for '$formName'.", 404);
    }
    
    return $formName;
  }
  
  public function actionSend() {
    $formName = $this->getFormName();
    
    $form = $this->module->getPlugin('formBroker')->createForm(
      $formName . '\SendForm', $this->request->getParam('form', array())
    );
    
    if ($form === null) {
      throw new \ultimo\mvc\exceptions\DispatchException("Missing form for {$formName}.", 404);
    }
    
    $mailFailed = false;
    $this->view->form = $form;
    $this->view->formName = $formName;
    
    if ($this->request->isPost()){
      if ($form->validate()) {
        // send form
        $settings = $this->config['forms'][$formName];
        
        // create search and replace arrays to replace variables
        $search = array();
        $replace = array();
        $replaceEscaped = array();
        foreach ($form->toArray(false) as $name => $value) {
          $search[] = '{{' . $name . '}}';
          $replace[] = $value;
          $replaceEscaped[] = htmlentities($value);
        }
        
        // build email
        $to = implode(', ', $settings['to']);
        $subject = str_replace($search, $replace, $settings['subject']);
        $message = $this->view->render('contact/' . $formName . '/message');
        $message = wordwrap($message, 70, "\r\n");
        
        // build headers
        $headers = array();
        if (isset($settings['from'])) {
          $headers[] = 'From: ' . str_replace($search, $replaceEscaped, $settings['from']);
        }
        
        if (isset($settings['content-type'])) {
          $headers[] = 'Content-type: ' . $settings['content-type'];
        }
        
        if (isset($settings['reply-to'])) {
          $headers[] = 'Reply-to: ' . $settings['reply-to'];
        }
        
        if (isset($settings['cc'])) {
          $headers[] = 'Cc: ' . implode(', ', $settings['cc']);
        }
        
        if (isset($settings['bcc'])) {
          $headers[] = 'Bcc: ' . implode(', ', $settings['bcc']);
        }
        
        if (isset($settings['headers'])) {
          $headers = array_merge($headers, $settings['headers']);
        }
        
        $mailFailed = !mail($to, $subject, $message, implode("\r\n", $headers));

        if (!$mailFailed) {
          return $this->getPlugin('redirector')->redirect(array('action' => 'finish', 'form_name' => $formName));
        }
      }
    }
    
    $this->view->mailFailed = $mailFailed;
  }
  
  public function actionFinish() {
    $formName = $this->getFormName();
    
    $this->view->formName = $formName;
  }
}