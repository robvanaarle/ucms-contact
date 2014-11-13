<?php

namespace ucms\contact\forms\simple;

class SendForm extends \ultimo\form\Form {
  
  protected function init() {
    $this->appendValidator('name', 'StringLength', array(1, 255));
    $this->appendValidator('email', 'EmailAddress');
    $this->appendValidator('message', 'StringLength', array(1, 65535));
  }
}