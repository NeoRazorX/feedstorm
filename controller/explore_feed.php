<?php

class explore_feed extends fs_controller
{
   public function __construct()
   {
      parent::__construct('explore_feed', 'Explorar fuente');
   }
   
   protected function process()
   {
      $encontrado = FALSE;
      
      if( isset($_GET['feed']) )
      {
         $feed = new feed();
         $feed0 = $feed->get($_GET['feed']);
         if( $feed0 )
         {
            $this->new_message("Historias de ".$feed0->name.'.');
            $this->stories = $feed0->get_stories();
            $this->visitor->add2log('Explorando fuente '.$feed0->name);
            $encontrado = TRUE;
         }
      }
      
      if( !$encontrado )
      {
         $this->new_error_msg('Fuente no encontrada.');
         $this->visitor->add2log('Explorando fuente '.$feed0->name.'. Fuente no encontrada.');
      }
   }
}

?>
