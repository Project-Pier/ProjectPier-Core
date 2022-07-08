<?php

  /**
  * ContactImValue class
  *
  * @http://www.projectpier.org/
  */
  class ContactImValue extends BaseContactImValue {
  
    /**
    * Return IM type
    *
    * @access public
    * @param void
    * @return ImType
    */
    function getImType() {
      return ImTypes::instance()->findById($this->getImTypeId());
    } // getImType
    
    /**
    * Return contact
    *
    * @access public
    * @param void
    * @return Contact
    */
    function getContact() {
      return Contacts::instance()->findById($this->getContactId());
    } // getContact
    
  } // ContactImValue 

?>