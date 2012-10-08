<?php

class select_feeds extends fs_controller
{
   public $feeds;
   
   public function __construct()
   {
      parent::__construct('select_feeds', 'Seleccionar fuentes de '.FS_NAME, 'select_feeds');
   }
   
   protected function process()
   {
      if( isset($_POST['feeds']) )
      {
         $this->visitor->clean_feeds();
         foreach($_POST['feeds'] as $fn)
            $this->visitor->add_feed($fn);
         
         $this->visitor->save_feeds();
         $this->new_message('Datos guardado correctamente.');
      }
      
      $feed = new feed();
      $this->feeds = $feed->all();
      foreach($this->feeds as &$f)
      {
         if( in_array($f, $this->visitor->get_feeds()) )
            $f->selected = TRUE;
      }
   }
}

?>
