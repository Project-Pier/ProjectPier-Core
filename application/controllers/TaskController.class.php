<?php

/**
* Controller for handling task list and task related requests
*
* @version 1.0
* @http://www.projectpier.org/
*/
class TaskController extends ApplicationController {

  /**
  * Construct the TaskController
  *
  * @access public
  * @param void
  * @return TaskController
  */
  function __construct() {
    parent::__construct();
    prepare_company_website_controller($this, 'project_website');
  } // __construct

  /**
  * Show index page
  *
  * @access public
  * @param void
  * @return null
  */
  function index() {
    $this->addHelper('textile');

    tpl_assign('open_task_lists', active_project()->getOpenTaskLists());
    tpl_assign('completed_task_lists', active_project()->getCompletedTaskLists());

    $this->canGoOn();

    $this->setSidebar(get_template_path('index_sidebar', 'task'));
  } // index


  /**
  * Download task list as attachment
  *
  * @access public
  * @param void
  * @return null
  */
  function download_list() {
    $task_list = ProjectTaskLists::instance()->findById(get_id());
    if (!($task_list instanceof ProjectTaskList)) {
      flash_error(lang('task list dnx'));
      $this->redirectTo('task');
    } // if
    $this->canGoOn();
    if (!$task_list->canView(logged_user())) {
      flash_error(lang('no access permissions'));
      $this->redirectToReferer(get_url('task'));
    } // if

    $output = array_var($_GET, 'output', 'csv');
    $project_name = active_project()->getName();
    $task_list_name = $task_list->getName();
    $task_count = 0;

    if ($output == 'pdf' ) {
      Env::useLibrary('fpdf');
      $download_name = "{$project_name}-tasks.pdf";
      $download_type = 'application/pdf';
      $pdf = new FPDF("P","mm");
      $pdf->AddPage();
      $pdf->SetTitle($project_name);
      $pdf->SetCompression(true);
      $pdf->SetCreator('ProjectPier');
      $pdf->SetDisplayMode('fullpage', 'single');
      $pdf->SetSubject(active_project()->getObjectName());
      $pdf->SetFont('Arial','B',16);
      $task_lists = active_project()->getOpenTaskLists();
      $pdf->Cell(0,10, lang('project') . ': ' . active_project()->getObjectName(),'B',0,'C');
      $pdf->Ln(14);
      $w = array( 0 => 12, 10, 20, 30, 40, 50, 60, 70, 80, 90, 100, 120, 140 );
      foreach($task_lists as $task_list) {
        $pdf->SetFont('Arial','B',14);
        $pdf->Write(10, lang('task list') . ': ' . $task_list->getObjectName());
        $pdf->Ln(14);
        $tasks = $task_list->getTasks();
        $pdf->SetFont('Arial','I',14);
        $pdf->SetFillColor(230,230,230);
        $pdf->Cell($w[1],6, '#',1,0,'C',true);
        $pdf->Cell($w[3],6, lang('status'),1,0,'C',true);
        $pdf->Cell($w[10],6, lang('info') ,1,0,'C',true);
        $pdf->Cell(0,6, lang(user) ,1,0,'C',true);
        $pdf->Ln();
        foreach($tasks as $task) {
          $line++;
          if ($task->isCompleted()) {
            $task_status = lang('completed');
            $task_status_color_R = 0;
            $task_status_color_G = 150;
            $task_status_color_B = 0;
            $task_completion_info = lang('completed task') . ' : ' . format_date($task->getCompletedOn()) . ' @ ' . format_time($task->getCompletedOn());
          } else {
            $task_status = lang('open');
            $task_status_color_R = 200;
            $task_status_color_G = 0;
            $task_status_color_B = 0;
            $task_completion_info = lang('due date') . ' : ' . lang('not assigned');
            $task_completion_info_color_R = 200;
            $task_completion_info_color_G = 0;
            $task_completion_info_color_B = 0;
            if ($task->getDueDate()) {
              $task_completion_info = lang('due date') . ' : ' . format_date($task->getDueDate()) . ' @ ' . format_time($task->getDueDate());
              $task_completion_info_color_R = 0;
              $task_completion_info_color_G = 0;
              $task_completion_info_color_B = 0;
            }
          }
          if ($task->getAssignedTo()) {
            $task_assignee = $task->getAssignedTo()->getObjectName();
            $task_assignee_color_R = 0;
            $task_assignee_color_G = 0;
            $task_assignee_color_B = 0;
          } else {
            $task_assignee = lang('not assigned');
            $task_assignee_color_R = 200;
            $task_assignee_color_G = 0;
            $task_assignee_color_B = 0;
          }
          $pdf->SetFillColor(245,245,245);
          $pdf->Cell($w[1],6, $line,1,0,'C',true);
          $pdf->SetTextColor($task_status_color_R, $task_status_color_G, $task_status_color_B);
          $pdf->Cell($w[3],6, $task_status,1,0,'C',true);
          $pdf->SetTextColor($task_completion_info_color_R, $task_completion_info_color_G, $task_completion_info_color_B);
          $pdf->Cell($w[10],6,$task_completion_info,1,0,'C',true);
          $pdf->SetTextColor($task_assignee_color_R, $task_assignee_color_G, $task_assignee_color_B);
          $pdf->Cell(0,6, $task_assignee ,1,0,'C',true);
          $pdf->SetTextColor(0, 0, 0);
          $pdf->Ln();
          $pdf->MultiCell(0,6,$task->getText(),1);
          //$pdf->Ln();
        }
      }

      $pdf->Output($download_name, 'I');

    }
    if ($output == 'txt' ) {
      $download_name = "{$project_name}-tasks.txt";
      $download_type = 'text/csv';
      $txt_lang_1 = lang('project');
      $txt_lang_2 = lang('milestone');
      $txt_lang_3 = lang('task list');
      $txt_lang_4 = lang('status');
      $txt_lang_5 = lang('description');
      $txt_lang_6 = lang('id');
      $txt_lang_7 = lang('status');
      $txt_lang_8 = lang('completion info');
      $txt_lang_9 = lang('assigned to');
      $s .= "$txt_lang_1\t$txt_lang_2\t$txt_lang_3\t$txt_lang_4\t$txt_lang_5\t$txt_lang_6\t$txt_lang_7\t$txt_lang_8\t$txt_lang_9";
      $s .= "\n";
      $task_lists = active_project()->getOpenTaskLists();
      foreach($task_lists as $task_list) {
        /*$s .= $task_list->getObjectName();
        $s .= "\n";
        $task_list_desc = $task_list->getDescription();
        $task_list_desc = strtr($task_list_desc,"\r\n\t","   ");
        $task_list_desc_100 = substr($task_list_desc,0,100);
        $s .= $task_list_desc_100;
        $s .= "\n";*/

        $milestone=$task_list->getMilestone();
        $tasks = $task_list->getTasks();
        foreach($tasks as $task) {
          $s .= $project_name;
          $s .= "\t";
          $milestone_name = lang(none);
          if ($milestone instanceof ProjectMilestone) {
            $milestone_name=$milestone->getName();
          }
          $s .= $milestone_name;
          $s .= "\t";
          $s .= $task_list->getObjectName();
          $s .= "\t";
          $task_list_name=$task_list->getName();
          if ($task_list->isCompleted()) {
            $task_list_status = lang('completed');
          } else {
            $task_list_status = lang('open');
          }
          $s .= $task_list_status;
          $s .= "\t";
          $task_list_desc2 = $task_list->getDescription();
          $task_list_desc2 = strtr($task_list_desc2,"\r\n\t","   ");
          $task_list_desc2_100 = substr($task_list_desc2,0,50);
          $s .= $task_list_desc2_100;
          $s .= "\t";
          $s .= $task->getId();
          $s .= "\t";
          if ($task->isCompleted()) {
            $task_status = lang('completed');
            $task_completion_info = format_date($task->getCompletedOn()) ." @ ". format_time($task->getCompletedOn());
          } else {
            $task_status = lang('open');
            $task_completion_info = format_date($task->getDueDate()) ." @ ". format_time($task->getDueDate());
          }
          $s .= $task_status;
          $s .= "\t";
          $s .= $task_completion_info;
          $s .= "\t";
          if ($task->getAssignedTo()) {
            $task_assignee = $task->getAssignedTo()->getObjectName();
          } else {
            $task_assignee = lang('not assigned');
          }
          $s .= $task_assignee;
          $s .= "\n";
        }
      }
      $download_contents = $s;
      download_headers( $download_name, $download_type, strlen($download_contents), true);
      echo $download_contents;
    } else {
      $download_name = "{$project_name}-{$task_list_name}-tasks.csv";
      $download_type = 'text/csv';
      $download_contents = $task_list->getDownloadText($task_count, "\t", true);
      download_contents($download_contents, $download_type, $download_name, strlen($download_contents));
    }
    die();
  }

  /**
  * View task lists page
  *
  * @access public
  * @param void
  * @return null
  */
  function view_list() {
    $this->addHelper('textile');
    $task_list = ProjectTaskLists::instance()->findById(get_id());
    if (!($task_list instanceof ProjectTaskList)) {
      flash_error(lang('task list dnx'));
      $this->redirectTo('task');
    } // if
    $this->canGoOn();
    if (!$task_list->canView(logged_user())) {
      flash_error(lang('no access permissions'));
      $this->redirectToReferer(get_url('task'));
    } // if

    tpl_assign('task_list', $task_list);

    // Sidebar
    tpl_assign('open_task_lists', active_project()->getOpenTaskLists());
    tpl_assign('completed_task_lists', active_project()->getCompletedTaskLists());
    $this->setSidebar(get_template_path('index_sidebar', 'task'));
  } // view_list

  /**
  * Add new task list
  *
  * @access public
  * @param void
  * @return null
  */
  function add_list() {

    if (!ProjectTaskList::canAdd(logged_user(), active_project())) {
      flash_error(lang('no access permissions'));
      $this->redirectToReferer(get_url('task'));
    } // if

    $task_list = new ProjectTaskList();
    $task_list->setProjectId(active_project()->getId());

    $task_list_data = array_var($_POST, 'task_list');
    if (!is_array($task_list_data)) {
      $task_list_data = array(
      'milestone_id' => array_var($_GET, 'milestone_id'),
      'start_date' => DateTimeValueLib::now(),
      'is_private' => config_option('default_private', false),
      'task0' => array( 'start_date' => DateTimeValueLib::now() ),
      'task1' => array( 'start_date' => DateTimeValueLib::now() ),
      'task2' => array( 'start_date' => DateTimeValueLib::now() ),
      'task3' => array( 'start_date' => DateTimeValueLib::now() ),
      'task4' => array( 'start_date' => DateTimeValueLib::now() ),
      'task5' => array( 'start_date' => DateTimeValueLib::now() ),
      ); // array
    } else {
      for ($i = 0; $i < 6; $i++) {
        $due_date = $_POST["task_list_task{$i}_due_date"];
        $task_list_data["task{$i}"]['due_date'] = $due_date;
        $start_date = $_POST["task_list_task{$i}_start_date"];
        $task_list_data["task{$i}"]['start_date'] = $start_date;
      }
    } // if

    tpl_assign('task_list_data', $task_list_data);
    tpl_assign('task_list', $task_list);

    if (is_array(array_var($_POST, 'task_list'))) {
      if (isset($_POST['task_list_start_date'])) {
        $task_list_data['start_date'] = DateTimeValueLib::makeFromString($_POST['task_list_start_date']);
      }
      if (isset($_POST['task_list_due_date'])) {
        $task_list_data['due_date'] = DateTimeValueLib::makeFromString($_POST['task_list_due_date']);
      }
      //$task_list_data['due_date'] = DateTimeValueLib::make(0, 0, 0, array_var($_POST, 'task_list_due_date_month', 1), array_var($_POST, 'task_list_due_date_day', 1), array_var($_POST, 'task_list_due_date_year', 1970));
      $task_list->setFromAttributes($task_list_data);
      if (!logged_user()->isMemberOfOwnerCompany()) {
        $task_list->setIsPrivate(false);
      }

      $tasks = array();
      for ($i = 0; $i < 6; $i++) {
        if (isset($task_list_data["task{$i}"]) && is_array($task_list_data["task{$i}"]) && (trim(array_var($task_list_data["task{$i}"], 'text')) <> '')) {
          $assigned_to = explode(':', array_var($task_list_data["task{$i}"], 'assigned_to', ''));
          if (isset($_POST["task_list_task{$i}_start_date"])) {
            $start_date = DateTimeValueLib::makeFromString($_POST["task_list_task{$i}_start_date"]);
          }
          if (isset($_POST["task_list_task{$i}_due_date"])) {
            $due_date = DateTimeValueLib::makeFromString($_POST["task_list_task{$i}_due_date"]);
          }
          $tasks[] = array(
          'text' => array_var($task_list_data["task{$i}"], 'text'),
          'order' => 1 + $i ,
          'start_date' => $start_date,
          'due_date' => $due_date,
          'assigned_to_company_id' => array_var($assigned_to, 0, 0),
          'assigned_to_user_id' => array_var($assigned_to, 1, 0),
          'send_notification' => array_var($task_list_data["task{$i}"], 'send_notification')
          ); // array
        } // if
      } // for

      try {

        DB::beginWork();
        $task_list->save();
        if (plugin_active('tags')) {
          $task_list->setTagsFromCSV(array_var($task_list_data, 'tags'));
        }

        foreach ($tasks as $task_data) {
          $task = new ProjectTask();
          $task->setFromAttributes($task_data);
          $task_list->attachTask($task);
          $task->save();

          tpl_assign('task', $task);
          // notify user
          if (array_var($task_data, 'send_notification') == 'checked') {
            try {
              $notify_people = array();
              $project_companies = array();

              if($task->getAssignedTo() == null)
              $project_companies = active_project()->getCompanies();
              if($task->getAssignedTo() instanceof Company)
              $project_companies = array($task->getAssignedTo());
              if($task->getAssignedTo() instanceof User)
              $notify_people = array($task->getAssignedTo());

              foreach($project_companies as $project_company) {
                $company_users = $project_company->getUsersOnProject(active_project());
                if(is_array($company_users))
                foreach($company_users as $company_user)
                $notify_people[] = $company_user;
              } // if

              Notifier::newTask($task, $notify_people);
            } catch(Exception $e) {
              Logger::log("Error: Notification failed, " . $e->getMessage(), Logger::ERROR);
            } // try
          } // if
        } // foreach

        ApplicationLogs::createLog($task_list, active_project(), ApplicationLogs::ACTION_ADD);
        DB::commit();

        flash_success(lang('success add task list', $task_list->getName()));
        $this->redirectToUrl($task_list->getViewUrl());

      } catch(Exception $e) {
        DB::rollback();
        tpl_assign('error', $e);
      } // try

    } // if

  } // add_list

  /**
  * Edit task list
  *
  * @access public
  * @param void
  * @return null
  */
  function edit_list() {
    $this->setTemplate('add_list');

    $task_list = ProjectTaskLists::instance()->findById(get_id());
    if (!($task_list instanceof ProjectTaskList)) {
      flash_error(lang('task list dnx'));
      $this->redirectTo('task');
    } // if

    if (!$task_list->canEdit(logged_user())) {
      flash_error(lang('no access permissions'));
      $this->redirectTo('task');
    } // if

    $task_list_data = array_var($_POST, 'task_list');
    if (!is_array($task_list_data)) {
      $tag_names = plugin_active('tags') ? $task_list->getTagNames() : '';
      $task_list_data = array(
      'name' => $task_list->getName(),
      'priority' => $task_list->getPriority(),
      'score' => $task_list->getScore(),
      'description' => $task_list->getDescription(),
      'start_date' => $task_list->getStartDate(),
      'due_date' => $task_list->getDueDate(),
      'milestone_id' => $task_list->getMilestoneId(),
      'tags' => is_array($tag_names) && count($tag_names) ? implode(', ', $tag_names) : '',
      'is_private' => $task_list->isPrivate()
      ); // array
    } // if
    tpl_assign('task_list', $task_list);
    tpl_assign('task_list_data', $task_list_data);

    if (is_array(array_var($_POST, 'task_list'))) {
      $old_is_private = $task_list->isPrivate();
      if (isset($_POST['task_list_start_date'])) {
        $task_list_data['start_date'] = DateTimeValueLib::makeFromString($_POST['task_list_start_date']);
      }
      if (isset($_POST['task_list_due_date'])) {
        $task_list_data['due_date'] = DateTimeValueLib::makeFromString($_POST['task_list_due_date']);
      }
      //$task_list_data['due_date'] = DateTimeValueLib::make(0, 0, 0, array_var($_POST, 'task_list_due_date_month', 1), array_var($_POST, 'task_list_due_date_day', 1), array_var($_POST, 'task_list_due_date_year', 1970));
      $task_list->setFromAttributes($task_list_data);
      if (!logged_user()->isMemberOfOwnerCompany()) {
        $task_list->setIsPrivate($old_is_private);
      }

      try {
        DB::beginWork();

        $task_list->save();
        if (plugin_active('tags')) {
          $task_list->setTagsFromCSV(array_var($task_list_data, 'tags'));
        }
        ApplicationLogs::createLog($task_list, active_project(), ApplicationLogs::ACTION_EDIT);

        DB::commit();

        flash_success(lang('success edit task list', $task_list->getName()));
        $this->redirectToUrl($task_list->getViewUrl());

      } catch(Exception $e) {
        DB::rollback();
        tpl_assign('error', $e);
      } // try
    } // if
  } // edit_list

  /**
  * Copy task list then redirect to edit
  *
  * @access public
  * @param void
  * @return null
  */
  function copy_list() {

    $task_list = ProjectTaskLists::instance()->findById(get_id());
    if (!($task_list instanceof ProjectTaskList)) {
      flash_error(lang('task list dnx'));
      $this->redirectTo('task', 'index');
    } // if

    if (!$task_list->canAdd(logged_user(), active_project())) {
      flash_error(lang('no access permissions'));
      $this->redirectTo('task', 'index');
    } // if

    try {
      DB::beginWork();

      $source_task_list = $task_list;
      $task_list = new ProjectTaskList();
      $task_list->setName($source_task_list->getName().' ('.lang('copy').')');
      $task_list->setPriority($source_task_list->getPriority());
      $task_list->setDescription($source_task_list->getDescription());
      $task_list->setMilestoneId($source_task_list->getMilestoneId());
      $task_list->setDueDate($source_task_list->getDueDate());
      $task_list->setIsPrivate($source_task_list->getIsPrivate());
      $task_list->setOrder($source_task_list->getOrder());
      $task_list->setProjectId($source_task_list->getProjectId());
      $task_list->save();
      $task_count = 0;
      $source_tasks = $source_task_list->getTasks();
      if (is_array($source_tasks)) {
        foreach($source_tasks as $source_task) {
          $task = new ProjectTask();
          $task->setText($source_task->getText());
          $task->setAssignedToUserId($source_task->getAssignedToUserId());
          $task->setAssignedToCompanyId($source_task->getAssignedToCompanyId());
          $task->setOrder($source_task->getOrder());
          $task->setDueDate($source_task->getDueDate());
          $task_list->attachTask($task);
          $task_count++;
        }
      }

      ApplicationLogs::createLog($task_list, active_project(), ApplicationLogs::ACTION_ADD);
      DB::commit();

      flash_success(lang('success copy task list', $source_task_list->getName(), $task_list->getName(), $task_count));
      //$this->redirectToUrl($task_list->getEditUrl());
      $this->redirectTo('task', 'index');

    } catch(Exception $e) {
      DB::rollback();
      tpl_assign('error', $e);
    } // try
  } // copy_list

  /**
  * Move task list
  *
  * @access public
  * @param void
  * @return null
  */
  function move_list() {
    $this->setTemplate('move_list');

    $task_list = ProjectTaskLists::instance()->findById(get_id());
    if (!($task_list instanceof ProjectTaskList)) {
      flash_error(lang('task list dnx'));
      $this->redirectTo('task', 'index');
    } // if

    if (!$task_list->canDelete(logged_user(), active_project())) {
      flash_error(lang('no access permissions'));
      $this->redirectTo('task', 'index');
    } // if

    $move_data = array_var($_POST, 'move_data');
    tpl_assign('task_list', $task_list);
    tpl_assign('move_data', $move_data);

    if (is_array($move_data)) {
      $target_project_id = $move_data['target_project_id'];
      $target_project = Projects::instance()->findById($target_project_id);
      if (!($target_project instanceof Project)) {
        flash_error(lang('project dnx'));
        $this->redirectToUrl($task_list->getMoveUrl());
      } // if
      if (!$task_list->canAdd(logged_user(), $target_project)) {
        flash_error(lang('no access permissions'));
        $this->redirectToUrl($task_list->getMoveUrl());
      } // if
      try {
        DB::beginWork();
        $task_list->setProjectId($target_project_id);
        $task_list->save();
        ApplicationLogs::createLog($task_list, active_project(), ApplicationLogs::ACTION_DELETE);
        ApplicationLogs::createLog($task_list, $target_project, ApplicationLogs::ACTION_ADD);
        DB::commit();

        flash_success(lang('success move task list', $task_list->getName(), active_project()->getName(), $target_project->getName() ));
      } catch(Exception $e) {
        DB::rollback();
        flash_error(lang('error move task list'));
      } // try

      $this->redirectToUrl($task_list->getViewUrl());
    }
  } // move_list

  /**
  * Delete task list
  *
  * @access public
  * @param void
  * @return null
  */
  function delete_list() {
    $this->setTemplate('del_list');

    $task_list = ProjectTaskLists::instance()->findById(get_id());
    if (!($task_list instanceof ProjectTaskList)) {
      flash_error(lang('task list dnx'));
      $this->redirectTo('task');
    } // if

    if (!$task_list->canDelete(logged_user())) {
      flash_error(lang('no access permissions'));
      $this->redirectTo('task');
    } // if

    $delete_data = array_var($_POST, 'deleteTaskList');
    tpl_assign('task_list', $task_list);
    tpl_assign('delete_data', $delete_data);

    if (!is_array($delete_data)) {
      $delete_data = array(
      'really' => 0,
      'password' => '',
      ); // array
      tpl_assign('delete_data', $delete_data);
    } else if ($delete_data['really'] == 1) {
      $password = $delete_data['password'];
      if (trim($password) == '') {
        tpl_assign('error', new Error(lang('password value missing')));
        return $this->render();
      }
      if (!logged_user()->isValidPassword($password)) {
        tpl_assign('error', new Error(lang('invalid login data')));
        return $this->render();
      }
      try {
        DB::beginWork();
        $task_list->delete();
        ApplicationLogs::createLog($task_list, active_project(), ApplicationLogs::ACTION_DELETE);
        DB::commit();

        flash_success(lang('success delete task list', $task_list->getName()));
      } catch(Exception $e) {
        DB::rollback();
        flash_error(lang('error delete task list'));
      } // try

      $this->redirectTo('task');
    } else {
      flash_error(lang('error delete task list'));
      $this->redirectToUrl($task_list->getViewUrl());
    }
  } // delete_list

  /**
  * Show and process reorder tasks form
  *
  * @param void
  * @return null
  */
  function reorder_tasks() {
    $task_list = ProjectTaskLists::instance()->findById(get_id('task_list_id'));
    if (!($task_list instanceof ProjectTaskList)) {
      flash_error(lang('task list dnx'));
      $this->redirectTo('task');
    } // if

    $back_to_list = (boolean) array_var($_GET, 'back_to_list');
    $redirect_to = $back_to_list ? $task_list->getViewUrl() : get_url('task');

    if (!$task_list->canReorderTasks(logged_user())) {
      flash_error(lang('no access permissions'));
      $this->redirectToUrl($redirect_to);
    } // if

    $tasks = $task_list->getOpenTasks();
    if (!is_array($tasks) || (count($tasks) < 1)) {
      flash_error(lang('no open task in task list'));
      $this->redirectToUrl($redirect_to);
    } // if

    tpl_assign('task_list', $task_list);
    tpl_assign('tasks', $tasks);
    tpl_assign('back_to_list', $back_to_list);

    if (array_var($_POST, 'submitted') == 'submitted') {
      $updated = 0;
      foreach ($tasks as $task) {
        $new_value = (integer) array_var($_POST, 'task_' . $task->getId());
        if ($new_value <> $task->getOrder()) {
          $task->setOrder($new_value);
          if ($task->save()) {
            $updated++;
          } // if
        } // if
      } // foreach

      flash_success(lang('success n tasks updated', $updated));
      $this->redirectToUrl($redirect_to);
    } // if
  } // reorder_tasks

  // ---------------------------------------------------
  //  Tasks
  // ---------------------------------------------------

  /**
  * Add single task
  *
  * @access public
  * @param void
  * @return null
  */
  function add_task() {
    $task_list = ProjectTaskLists::instance()->findById(get_id('task_list_id'));
    if (!($task_list instanceof ProjectTaskList)) {
      flash_error(lang('task list dnx'));
      $this->redirectTo('task');
    } // if

    if (!$task_list->canAddTask(logged_user())) {
      flash_error(lang('no access permissions'));
      $this->redirectTo('task');
    } // if

    $back_to_list = array_var($_GET, 'back_to_list');

    $task = new ProjectTask();
    $task_data = array_var($_POST, 'task');
    if (!is_array($task_data)) {
      $task_data = array(
      'due_date' => DateTimeValueLib::now(),
      ); // array
    } // if

    tpl_assign('task', $task);
    tpl_assign('task_list', $task_list);
    tpl_assign('back_to_list', $back_to_list);
    tpl_assign('task_data', $task_data);

    // Form is submitted
    if (is_array(array_var($_POST, 'task'))) {
      $old_owner = $task->getAssignedTo();
      //$task_data['due_date'] = DateTimeValueLib::make(0, 0, 0, array_var($_POST, 'task_due_date_month', 1), array_var($_POST, 'task_due_date_day', 1), array_var($_POST, 'task_due_date_year', 1970));
      if (isset($_POST['task_start_date'])) {
        $task_data['start_date'] = DateTimeValueLib::makeFromString($_POST['task_start_date']);
      } else {
        $task_data['start_date'] = DateTimeValueLib::make(0, 0, 0, array_var($_POST, 'task_start_date_month', 1), array_var($_POST, 'task_start_date_day', 1), array_var($_POST, 'task_start_date_year', 1970));
      }
      if (isset($_POST['task_due_date'])) {
        $task_data['due_date'] = DateTimeValueLib::makeFromString($_POST['task_due_date']);
      } else {
        $task_data['due_date'] = DateTimeValueLib::make(0, 0, 0, array_var($_POST, 'task_due_date_month', 1), array_var($_POST, 'task_due_date_day', 1), array_var($_POST, 'task_due_date_year', 1970));
      }
      $task->setFromAttributes($task_data);

      $assigned_to = explode(':', array_var($task_data, 'assigned_to', ''));
      $task->setAssignedToCompanyId(array_var($assigned_to, 0, 0));
      $task->setAssignedToUserId(array_var($assigned_to, 1, 0));

      try {

        DB::beginWork();
        $task->save();
        $task_list->attachTask($task);
        ApplicationLogs::createLog($task, active_project(), ApplicationLogs::ACTION_ADD);
        DB::commit();

        // notify user
        if (array_var($task_data, 'send_notification') == 'checked') {
          try {
            $notify_people = array();
            $project_companies = array();

            if($task->getAssignedTo() == null)
            $project_companies = active_project()->getCompanies();
            if($task->getAssignedTo() instanceof Company)
            $project_companies = array($task->getAssignedTo());
            if($task->getAssignedTo() instanceof User)
            $notify_people = array($task->getAssignedTo());

            foreach($project_companies as $project_company) {
              $company_users = $project_company->getUsersOnProject(active_project());
              if(is_array($company_users))
              foreach($company_users as $company_user)
              $notify_people[] = $company_user;
            } // if

            Notifier::newTask($task, $notify_people);
          } catch(Exception $e) {
            Logger::log("Error: Notification failed, " . $e->getMessage(), Logger::ERROR);
          } // try
        } // if

        flash_success(lang('success add task'));
        if ($back_to_list) {
          $this->redirectToUrl($task_list->getViewUrl());
        } else {
          $this->redirectTo('task');
        } // if

      } catch(Exception $e) {
        DB::rollback();
        tpl_assign('error', $e);
      } // try

    } // if
  } // add_task

  /**
  * Edit task
  *
  * @access public
  * @param void
  * @return null
  */
  function edit_task() {
    $this->setTemplate('add_task');

    $task = ProjectTasks::instance()->findById(get_id());
    if (!($task instanceof ProjectTask)) {
      flash_error(lang('task dnx'));
      $this->redirectTo('task');
    } // if

    $task_list = $task->getTaskList();
    if (!($task_list instanceof ProjectTaskList)) {
      flash_error('task list dnx');
      $this->redirectTo('task');
    } // if

    if (!$task->canEdit(logged_user())) {
      flash_error(lang('no access permissions'));
      $this->redirectTo('task');
    } // if

    $task_data = array_var($_POST, 'task');
    if (!is_array($task_data)) {
      $task_data = array(
      'text' => $task->getText(),
      'start_date' => $task->getStartDate(),
      'due_date' => $task->getDueDate(),
      'task_list_id' => $task->getTaskListId(),
      'assigned_to' => $task->getAssignedToCompanyId() . ':' . $task->getAssignedToUserId(),
      'send_notification' => config_option('send_notification_default', '0')
      ); // array
    } // if

    tpl_assign('task', $task);
    tpl_assign('task_list', $task_list);
    tpl_assign('task_data', $task_data);

    if (is_array(array_var($_POST, 'task'))) {
      $old_owner = $task->getAssignedTo();
      //$task_data['due_date'] = DateTimeValueLib::make(0, 0, 0, array_var($_POST, 'task_due_date_month', 1), array_var($_POST, 'task_due_date_day', 1), array_var($_POST, 'task_due_date_year', 1970));
      if (isset($_POST['task_start_date'])) {
        $task_data['start_date'] = DateTimeValueLib::makeFromString($_POST['task_start_date']);
      } else {
        $task_data['start_date'] = DateTimeValueLib::make(0, 0, 0, array_var($_POST, 'task_start_date_month', 1), array_var($_POST, 'task_start_date_day', 1), array_var($_POST, 'task_start_date_year', 1970));
      }
      if (isset($_POST['task_due_date'])) {
        $task_data['due_date'] = DateTimeValueLib::makeFromString($_POST['task_due_date']);
      } else {
        $task_data['due_date'] = DateTimeValueLib::make(0, 0, 0, array_var($_POST, 'task_due_date_month', 1), array_var($_POST, 'task_due_date_day', 1), array_var($_POST, 'task_due_date_year', 1970));
      }
      $task->setFromAttributes($task_data);
      $task->setTaskListId($task_list->getId()); // keep old task list id

      $assigned_to = explode(':', array_var($task_data, 'assigned_to', ''));
      $task->setAssignedToCompanyId(array_var($assigned_to, 0, 0));
      $task->setAssignedToUserId(array_var($assigned_to, 1, 0));

      try {
        DB::beginWork();
        $task->save();

        // Move?
        $new_task_list_id = (integer) array_var($task_data, 'task_list_id');
        if ($new_task_list_id && ($task->getTaskListId() <> $new_task_list_id)) {

          // Move!
          $new_task_list = ProjectTaskLists::instance()->findById($new_task_list_id);
          if ($new_task_list instanceof ProjectTaskList) {
            $task_list->detachTask($task, $new_task_list); // detach from old and attach to new list
          } // if

        } // if

        ApplicationLogs::createLog($task, active_project(), ApplicationLogs::ACTION_EDIT);
        DB::commit();
        trace(__FILE__,'edit_task: notify user');
        // notify user
        if (array_var($task_data, 'send_notification') == 'checked') {
          try {
            if (Notifier::notifyNeeded($task->getAssignedTo(), $old_owner)) {
              Notifier::taskAssigned($task);
            }
          } catch(Exception $e) {
            Logger::log("Error: Notification failed, " . $e->getMessage(), Logger::ERROR);
          } // try
        } // if

        flash_success(lang('success edit task'));

        // Redirect to task list. Check if we have updated task list ID first
        if (isset($new_task_list) && ($new_task_list instanceof ProjectTaskList)) {
          $this->redirectToUrl($new_task_list->getViewUrl());
        } else {
          $this->redirectToUrl($task_list->getViewUrl());
        } // if

      } catch(Exception $e) {
        DB::rollback();
        tpl_assign('error', $e);
      } // try

    } // if

  } // edit_task

  /**
  * http://haris.tv htv edit
  * View task details page
  *
  * @access public
  * @param void
  * @return null
  */
  function view_task() {
    $this->setTemplate('view_task');
    $this->addHelper('textile');

    // taken from edit_task - htv
    $task = ProjectTasks::instance()->findById(get_id());
    if(!($task instanceof ProjectTask)) {
      flash_error(lang('task dnx'));
      $this->redirectTo('task');
    } // if

    $task_list = $task->getTaskList();
    if(!($task_list instanceof ProjectTaskList)) {
      flash_error('task list dnx');
      $this->redirectTo('task');
    } // if

    if(!$task->canView(logged_user())) {
      flash_error(lang('no access permissions'));
      $this->redirectTo('task');
    } // if

    $task_data = array_var($_POST, 'task');
    if(!is_array($task_data)) {
      $task_data = array(
      'text' => $task->getText(),
      'due_date' => $task->getDueDate(),
      'task_list_id' => $task->getTaskListId(),
      'assigned_to' => $task->getAssignedToCompanyId() . ':' . $task->getAssignedToUserId()
      ); // array
    } // if

    tpl_assign('task', $task);
    tpl_assign('task_list', $task_list);
    tpl_assign('task_data', $task_data);

  } // task_details

  /**
  * Delete specific task
  *
  * @access public
  * @param void
  * @return null
  */
  function delete_task() {
    $this->setTemplate('del_task');

    $task = ProjectTasks::instance()->findById(get_id());
    if (!($task instanceof ProjectTask)) {
      flash_error(lang('task dnx'));
      $this->redirectTo('task');
    } // if

    $task_list = $task->getTaskList();
    if (!($task_list instanceof ProjectTaskList)) {
      flash_error('task list dnx');
      $this->redirectTo('task');
    } // if

    if (!$task->canDelete(logged_user())) {
      flash_error(lang('no access permissions'));
      $this->redirectTo('task');
    } // if

    $delete_data = array_var($_POST, 'deleteTask');
    tpl_assign('task', $task);
    tpl_assign('task_list', $task_list);
    tpl_assign('delete_data', $delete_data);

    if (!is_array($delete_data)) {
      $delete_data = array(
      'really' => 0,
      'password' => '',
      ); // array
      tpl_assign('delete_data', $delete_data);
    } else if ($delete_data['really'] == 1) {
      $password = $delete_data['password'];
      if (trim($password) == '') {
        tpl_assign('error', new Error(lang('password value missing')));
        return $this->render();
      }
      if (!logged_user()->isValidPassword($password)) {
        tpl_assign('error', new Error(lang('invalid login data')));
        return $this->render();
      }
      try {
        DB::beginWork();
        $task->delete();
        ApplicationLogs::createLog($task, active_project(), ApplicationLogs::ACTION_DELETE);
        DB::commit();

        flash_success(lang('success delete task'));
      } catch(Exception $e) {
        DB::rollback();
        flash_error(lang('error delete task'));
      } // try

      $this->redirectToUrl($task_list->getViewUrl());
    } else {
      flash_error(lang('error delete task'));
      $this->redirectToUrl($task_list->getViewUrl());
    }
  } // delete_task

  /**
  * Complete single project task
  *
  * @access public
  * @param void
  * @return null
  */
  function complete_task() {
    $task = ProjectTasks::instance()->findById(get_id());
    if (!($task instanceof ProjectTask)) {
      flash_error(lang('task dnx'));
      $this->redirectTo('task');
    } // if

    $task_list = $task->getTaskList();
    if (!($task_list instanceof ProjectTaskList)) {
      flash_error(lang('task list dnx'));
      $this->redirectTo('task');
    } // if

    if (!$task->canChangeStatus(logged_user())) {
      flash_error(lang('no access permissions'));
      $this->redirectTo('task');
    } // if

    $redirect_to = array_var($_GET, 'redirect_to');
    if (!is_valid_url($redirect_to)) {
      $redirect_to = get_referer($task_list->getViewUrl());
    } // if

    try {
      DB::beginWork();
      $task->completeTask();
      ApplicationLogs::createLog($task, active_project(), ApplicationLogs::ACTION_CLOSE);
      DB::commit();

      flash_success(lang('success complete task'));
    } catch(Exception $e) {
      flash_error(lang('error complete task'));
      DB::rollback();
    } // try

    $this->redirectToUrl($redirect_to);
  } // complete_task

  /**
  * Reopen completed project task
  *
  * @access public
  * @param void
  * @return null
  */
  function open_task() {
    $task = ProjectTasks::instance()->findById(get_id());
    if (!($task instanceof ProjectTask)) {
      flash_error(lang('task dnx'));
      $this->redirectTo('task');
    } // if

    $task_list = $task->getTaskList();
    if (!($task_list instanceof ProjectTaskList)) {
      flash_error(lang('task list dnx'));
      $this->redirectTo('task');
    } // if

    if (!$task->canChangeStatus(logged_user())) {
      flash_error(lang('no access permissions'));
      $this->redirectTo('task');
    } // if

    $redirect_to = array_var($_GET, 'redirect_to');
    if ((trim($redirect_to) == '') || !is_valid_url($redirect_to)) {
      $redirect_to = get_referer($task_list->getViewUrl());
    } // if

    try {
      DB::beginWork();
      $task->openTask();
      ApplicationLogs::createLog($task, active_project(), ApplicationLogs::ACTION_OPEN);
      DB::commit();

      flash_success(lang('success open task'));
    } catch(Exception $e) {
      flash_error(lang('error open task'));
      DB::rollback();
    } // try

    $this->redirectToUrl($redirect_to);
  } // open_task

  /**
  * Reopen completed project task
  *
  * @access public
  * @param void
  * @return null
  */
  function edit_score() {
    $task = ProjectTasks::instance()->findById(get_id());
    if (!($task instanceof ProjectTask)) {
      flash_error(lang('task dnx'));
      //$this->redirectTo('task');
    } // if

    include '../views/editscore.html';

  } // open_task


} // TaskController

?>
