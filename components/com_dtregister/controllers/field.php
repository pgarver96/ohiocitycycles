<?php

/**
* @version 2.7.2
* @package Joomla 1.5
* @subpackage DT Register
* @copyright Copyright (C) 2006 DTH Development
* @copyright contact dthdev@dthdevelopment.com
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

class DtregisterControllerField extends DtrController {
   
   var $name = "field";
   
    function __construct($config = array()){
		 
		 $config = array('default_task'=>'view','task_map'=>array('new'=>'add','cancel'=>'view'));
		 parent::__construct($config);
		 $this->view = & $this->getView( 'field', 'html' );
		 $this->view->setModel($this->getModel('field'));
		 $this->view->setModel($this->getModel('fieldtype'),true);

		 $this->registerTask( 'cancel',  'view' );

		 JToolBarHelper::title(  JText::_( 'DT_FIELD_MGMT'), 'dtregister' );

	}
	
	function getConditonField(){
	    
	  global $mainframe; 
      $field = $this->getModel('field')->table;
     $document =& JFactory::getDocument();
	 if(isset($document->_script["text/javascript"])){
	  unset($document->_script["text/javascript"]);
	 }
	  $field->load(JRequest::getVar('field_id',0));
	  
	  $childs = $field->getchild($published=1);
	  //pr($childs);
	  $selectionindex = JRequest::getVar('selection','');
	  $not_remove =  JRequest::getVar('not_remove',array());
	  if(!is_array($not_remove)){
		   $not_remove =  array($not_remove);
	  }
	  if($field->type == 1){
		 $selectionindex--;
	  }
	 
	  $elements = array();

	  $childjs = "";
	  $user = unserialize(base64_decode(JRequest::getVar('obj','')));
	  $eventId = JRequest::getVar('eventId','');
	  $event =  DtrTable::getInstance('event','Table');
	  $event->load($eventId);

	  $remove_elements = array();
	  $ordering =  array();
      require_once(JPATH_SITE."/components/com_dtregister/views/field/view.html.php");
	  $fieldView = new DtregisterViewField(array());
	  
	   if (is_array($fieldView->_path['template'])) 
	   foreach($fieldView->_path['template'] as $path){
	      if(file_exists($path)){
		     $basepath = $path;
			 break;
		  }
	   }
	   
	   if (is_array($childs)) 
	   foreach($childs as $key=>$child){
	  
		 $selection =  explode('|',$child->selection_values);
		 $ordering[$child->id] = $child->ordering;
		 $selectionindex;
		 $fieldType =  DtrModel::getInstance('Fieldtype','DtregisterModel');
		 $fieldTypes =  $fieldType->getTypes();
		
		 if(in_array($selectionindex,$selection)){
			$class = "Field_".$fieldTypes[$child->type];
			$childObj =  new $class();
			$childObj->load($child->id);

			 $file = $basepath."field_".$fieldTypes[$child->type].".php";
			 
			  if (!file_exists($file)) {
				 $file = $basepath."default.php";
			  }
			  $child->label = stripslashes($child->label).":";
			  if($child->required){
				$child->label = $child->label." <span class='dtrequired'>&nbsp;&nbsp;*&nbsp;&nbsp;</span> ";
			  }
			  
			  $tpl = file_get_contents($file);
			  $constants = array('[label]','[value]','[description]');
			  $description =  (trim($child->description)!="")?JHTML::tooltip($child->description, '', 'tooltip.png', '', ''):'';
			  $replace  = array($child->label,$childObj->formhtml($user,$event,'frmcart',true),$description);
			  $childjs .= $childObj->childJs;
			  $elements[$child->id] = str_replace($constants,$replace,$tpl);
			
		 }else{
		   $remove = true;
			
			if (is_array($selection)) 
			foreach($selection as $val){
				if(in_array($val,$not_remove)){
				   $remove = false;
				   break;
				} 
			}
			
			if($remove){
			  $remove_elements[$child->id] = $child->id;
			}
		 }

	  }
	  $temp = $remove_elements;
  
     foreach($remove_elements as $remove_element){
	  $rChilds = array();
	  
	  $field->findtree($remove_element,$rChilds);
	  
	  foreach($rChilds as $rchild){
		  if(!in_array($rchild->id,array_keys($elements)))
		  $temp[$rchild->id] = $rchild->id;
	  }
	
 }

 $remove_elements = $temp;
 ob_start();
	 ?>
   
	 var elements = <?php echo json_encode($elements); ?>;
	  var remove_elements = <?php echo json_encode($remove_elements); ?>;
      var ordering = <?php echo json_encode($ordering); ?>;
     <?php 
	 echo $childjs;
	 echo  chr(13);
	 if(isset($document->_script["text/javascript"])){
	 echo $document->_script['text/javascript'];
	 }
	$data = ob_get_clean();
	  
	 echo str_replace("\n",' ',$data)."/*DTendScript*/";
	 die;
	   
	}
	
	 function remove(){

      global $mainframe ,$Itemid;

	  $field = $this->getModel('field')->table;

	  $cid = JRequest::getVar( 'cid', array(0), 'request', 'array' );

	  if (is_array($cid)) 
	  foreach($cid as $id){

	      $field->load($id);

		  $field->delete();      

	  }

	 $mainframe->redirect( "index.php?option=".DTR_COM_COMPONENT."&controller=field&Itemid=$Itemid" );

   }

   function publish(){

      $cid = JRequest::getVar( 'cid', array(0), 'request', 'array' );

	  $field = $this->getModel('field')->table;

	   if (is_array($cid)) 
	   foreach($cid as $id){

	      $field->load($id);

		   $field->published = 1;

		  $field->store();      

	  }

	  $this->view();

   }

   function unpublish(){

      $cid = JRequest::getVar( 'cid', array(0), 'request', 'array' );

	  $field = $this->getModel('field')->table;

	   if (is_array($cid)) 
	   foreach($cid as $id){

	      $field->load($id);

		  $field->published = 0;

		  $field->store();      

	  }

	  $this->view();

   }

   function orderup(){

	  $this->order(-1);

   }

   function saveorder(){

      global $mainframe ,$Itemid;

	  $row = $this->getModel('field')->table;

	  $cid = JRequest::getVar( 'cid', array(0), 'request', 'array' );

	  $order	= JRequest::getVar( 'order', array(0), '', 'array' );

	  $total		= count( $cid );

	  for( $i=0; $i < $total; $i++ ) {

		$row->load( (int) $cid[$i] );

		if ($row->ordering != $order[$i]) {

			$row->ordering = $order[$i];

            $row->store();

			if ($row->getError()) {

				JError::raiseError(500, $row->getError() );

				exit();

			}

		}

	 }

	  $mainframe->redirect( "index.php?option=com_dtregister&controller=field&Itemid=".$Itemid );

   }

   function orderdown(){

	  $this->order(1);

   }

   function order($inc){

       global $mainframe ,$Itemid;

	   $row = $this->getModel('field')->table;

       $cid = JRequest::getVar( 'cid', array(0), 'request', 'array' );

	   $uid = $cid[0];

	   $row->load( (int)$uid );

	   $row->reorder();

	   $row->move( $inc, true );

	 $mainframe->redirect( "index.php?option=".DTR_COM_COMPONENT."&controller=field&Itemid=".$Itemid );

   }

   function view(){

   	   JToolBarHelper::publishList('publish');

    	JToolBarHelper::divider();

		  JToolBarHelper::unpublishList('unpublish');

    	JToolBarHelper::divider();

		  JToolBarHelper::editListX('edit');

    	JToolBarHelper::divider();

		  JToolBarHelper::deleteList('Are you sure want to delete selected customfield','remove');

	    JToolBarHelper::divider();

		  JToolBarHelper::editList('copyField', JText::_( 'DT_COPY_FIELD'));

    	JToolBarHelper::divider();

		  JToolBarHelper::addNewX('add');

	global $mainframe;

	 $option = DTR_COM_COMPONENT;

	jimport('joomla.html.pagination');

	$listLimit = $mainframe->getCfg( 'list_limit', 10 );

	$database = &JFactory::getDBO();

	$limit 		= intval( $mainframe->getUserStateFromRequest( "viewlistlimit", 'limit', $listLimit ) );

    $limit		= $mainframe->getUserStateFromRequest( 'global.list.limit', 'limit', $mainframe->getCfg('list_limit'), 'int' );

	$limitstart = intval( $mainframe->getUserStateFromRequest( "view{$option}limitstart", 'limitstart', 0 ) );

	$search	= $mainframe->getUserStateFromRequest( "search{$option}", 'search', '' );

	if (get_magic_quotes_gpc()) {

		$search	= stripslashes( $search );

	}

	$where = array();

	if ($search) {

		$where[] = "LOWER(name) LIKE '%" . $database->getEscaped( trim( strtolower( $search ) ) ) . "%'";

	}

    $rows = $this->getModel('field')->table->find((count( $where ) ? "\n " . implode( ' AND ', $where ) : ""),' ordering ',$limitstart, $limit);

	$total = $this->getModel('field')->lastQueryCount();

	$pageNav = new DtPagination( $total, $limitstart, $limit );
    $pageNav->form = "adminForm";
	if ($database->getErrorNum()) {

		echo $database->stderr();

		return false;

	}

       $this->view->assign('rows',$rows);

	   $this->view->assign('pageNav',$pageNav);

	   $this->view->assign('search',$search);

	   $this->view->setLayout('list');

	   $this->view->display();

   }

   function add(){

       JToolBarHelper::save('save',JText::_( 'DT_SAVE'));

       JToolBarHelper::divider();

       JToolBarHelper::cancel('cancel');

	  $this->view->setLayout('add');

	  $this->view->display();

   }

   function copyField(){

	  $this->view->assign('copyfield',true);

	  $this->add();

   }

   function edit(){

       JToolBarHelper::save('save',JText::_( 'DT_SAVE'));

       JToolBarHelper::divider();

       JToolBarHelper::cancel( 'cancel', JText::_( 'DT_CLOSE') );

	  $this->view->setLayout('add');

	  $this->view->display();

   }

   function save(){

	global $mainframe;

	$database = &JFactory::getDBO();

	$row = $this->getModel('field')->table;

	$id=JRequest::getInt('id',0);

	if($id){

	  $row->load($id);

	}else{

	   $name=JRequest::getVar('name','');

	   $query = "Select * from #__dtregister_fields where name = '$name'";

	   $database->setQuery($query);

	   $database->query();

	   $num_rows = $database->getNumRows();

	   if($num_rows > 0){

		  $this->add();

		  return;

	   }

	}

    $_POST['label'] = html_entity_decode($_POST['label']);

	$_POST['listing'] = implode("|",$_POST['listing']);

	if (!$row->bind( $_POST )) {
	pr($row->getError()." bind error");

		exit();

	}

	if (!$row->check()) {

       pr($row->getError()." bind check");

		exit();

	}

	//Store record

    $row->values = stripslashes($row->values);

	if ($row->store() !== null) {

		pr($row->getError()." store check");

		exit();

	}

    if($row->allevent == 1){

	   $row->enableALLEvents();

	}

	$mainframe->redirect( "index.php?option=".DTR_COM_COMPONENT."&controller=field&Itemid=".$Itemid );

}

   function options(){

       $this->view->setLayout('options');

	   $this->view->display();

   }

}

?>