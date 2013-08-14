<?php

defined('MOODLE_INTERNAL') || die();

function bootstrap_user_settings($css, $theme) {
    global $CFG;
    if (!empty($theme->settings->customcss)) {
        $customcss = $theme->settings->customcss;
    } else {
        $customcss = null;
    }

    $tag = '[[setting:customcss]]';
    $css = str_replace($tag, $customcss, $css);

    
    if ($theme->settings->enableglyphicons == 1) {
        $bootstrapicons = '
        [class ^="icon-"],[class *=" icon-"] { background-image: url("'.$CFG->wwwroot.'/theme/image.php?theme=bootstrap&component=theme&image=glyphicons-halflings"); }';
        $css .= $bootstrapicons;
    }

    return $css;
}




// cubic stuff follows


/**
 * Get user picture.
 * @param int $size - picture size.
 * @return string (HTML)
 */
function bootstrap_get_user_picture($size) {
	global $PAGE, $OUTPUT, $USER;
	
	$html = '';
	if(isloggedin() && !isguestuser()) {
		$html.= $OUTPUT->user_picture($USER, array('size' => $size));
	}
	else {
		if($size > 35) {
			$html.= '<img src="'.$OUTPUT->pix_url('g/f1').'" />';
		}
		else {
			$html.= '<img src="'.$OUTPUT->pix_url('g/f2').'" />';
		}
	}
	return $html;
}


/**
 * Get current logged user courses.
 * @return array
 */
function bootstrap_get_courses_list($fields=null) {
	global $CFG;
	
	include_once($CFG->dirroot . '/course/lib.php');
	return enrol_get_my_courses($fields, 'visible DESC, fullname ASC');
}

/**
 * Get current logged user events for his courses.
 * @param int $records - num of records to search for.
 * @return array
 */
function bootstrap_get_events_list($records, $count=false) {
	global $CFG;
	
	if(isloggedin() && !isguestuser()) {
		
		//Filter Events by Course
		include_once($CFG->dirroot .'/calendar/lib.php');
		$filtercourse = calendar_get_default_courses();
		list($coursesIds, $group, $user) = calendar_set_filters($filtercourse);

		$key = array_search(1, $coursesIds);
		unset($coursesIds[$key]);

		//Get User Preferences for Calendar
		if($count) {
			$lookahead = 7;

			//Count Events
			return count(calendar_get_upcoming($coursesIds, $group, $user, $lookahead, $records));
		}
		else {
			$defaultlookahead = CALENDAR_DEFAULT_UPCOMING_LOOKAHEAD;
	    	if (isset($CFG->calendar_lookahead)) {
	    		$defaultlookahead = intval($CFG->calendar_lookahead);
	    	}
			$lookahead = get_user_preferences('calendar_lookahead', $defaultlookahead);

			//Get Events
			return calendar_get_upcoming($coursesIds, $group, $user, $lookahead, $records);
		}
	}
	else {
		return array();
	}
}

/**
 * Get current logged user notifications for his courses.
 * @param array $coursesList - current user courses.
 * @param int $records - num of records to search for.
 * @param bool $count - determines if its returned the number or the list of notifications.
 * @param bool $recent - if true the query will search for notifications only for the current day.
 * @return string or array
 */
function bootstrap_get_notifications_list($coursesList, $records=0, $count=false, $recent=false) {
	global $CFG, $DB, $USER;
	
	if(isloggedin() && !isguestuser()) {
		
		//Get User Courses
		$i = 0;
		$courses = '';
		$extra = '';
		$len = count($coursesList);

		if($len > 0) {

			foreach($coursesList as $course) {
				$courses.= $course->id;

				if($i != $len - 1) {
					$courses.=', ';
				}

				$i++;
			}

			//Set Notifications Date
			if($recent) {
				$extra.= 'AND DATE(FROM_UNIXTIME({forum_posts}.modified)) = DATE(NOW()) ';
			}
			
			// Set User (if counting do not count the current user posts)
			if($count) {
				//$extra = 'AND {forum_posts}.userid != '.$USER->id;
			}

			//Set Limit
			if($records > 0) {
				$records = ' LIMIT '.$records;
			}
			else {
				$records = ';';
			}

			//Query
			$query = 
				'SELECT {forum_posts}.id as idP, {forum_posts}.discussion, {forum_posts}.subject, '.
					'{forum_posts}.message, {forum_posts}.modified, {forum_posts}.userid, '.
					'{forum_discussions}.id as idD, {forum_discussions}.forum, '.
					'{forum}.id as idF, {forum}.course, {forum}.type '.
				'FROM {forum} JOIN {forum_discussions} ON {forum}.id = {forum_discussions}.forum '.
				'JOIN {forum_posts} ON {forum_discussions}.id = {forum_posts}.discussion '.
				'WHERE {forum}.type = "news" '.$extra.' AND {forum}.course IN ('.$courses.') '.
				'ORDER BY {forum_posts}.modified DESC'.$records;
				
			$list = $DB->get_records_sql($query);

			return $count? count($list) : $list;
		}
		else {
			return $count? 0 : array();
		}
	}
	else {
		return array();
	}
}

/**
 * Count notifications for current day.
 * @return string (HTML)
 */
function bootstrap_get_notifications_count() {
	global $USER;
	
	$html = '';
	if(isloggedin() && !isguestuser()) {
		$notifications = bootstrap_get_notifications_list(bootstrap_get_courses_list(), 0, true, true);

		if($notifications > 0 && $notifications < 100) {
			$html = '<span class="counter">'.$notifications.'</span>';
		}
		else if($notifications >= 100) {
			$html = '<span class="counter">99</span>';
		}
	}
	
	return $html;
}

/**
 * Count events for next week.
 * @return string (HTML)
 */
function bootstrap_get_events_count() {
	global $USER;
	
	$html = '';
	if(isloggedin() && !isguestuser()) {
		$events = bootstrap_get_events_list(99, true);

		if($events > 0 && $events < 100) {
			$html = '<span class="counter">'.$events.'</span>';
		}
		else if($events >= 100) {
			$html = '<span class="counter">99</span>';
		}
	}
	return $html;
}

/**
 * Count unread messages.
 * @return string (HTML)
 */
function bootstrap_get_messages_count() {
	global $DB, $USER;
	
	$html = '';
	if(isloggedin() && !isguestuser()) {
		//Get Number of Unread Messages
		$messages = $DB->count_records('message', array('useridto' => $USER->id));

		if($messages > 0 && $messages < 100) {
			$html = '<span class="counter">'.$messages.'</span>';
		}
		else if($messages >= 100) {
			$html = '<span class="counter">99</span>';
		}
	}
	return $html;
}


/**
 * Get current user notifications menu.
 * @param string $title - menu title.
 * @param int $records - num of records to search for.
 * @return string (HTML)
 */
function bootstrap_get_user_notifications($records=0) {
	global $DB, $CFG, $OUTPUT;
	
	$title = get_string('menu-notifications','theme_cubic');
	
	$html = '';
	if(isloggedin() && !isguestuser()) {
		
		if(!$records) {
			$html.= '<div class="title">'.$title.'</div><div class="ajax"></div><div class="container">';
		}
		else {
			$hasNotifications = false;

			//Get Notifications of Current Course
			$coursesList = bootstrap_get_courses_list('id, shortname');
			$notificationsList = bootstrap_get_notifications_list($coursesList, $records);

			foreach($notificationsList as $notification) {
				$hasNotifications = true;

				$user = $DB->get_record('user', array('id' => $notification->userid), 'id, firstname, lastname, email, picture, imagealt');

				$html.=
					'<div class="notification">'.
						'<a href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$notification->idd.'#p'.$notification->idp.'" />'.
						'<div class="picture">'.
							$OUTPUT->user_picture($user, array('size' => 50)).
						'</div>'.
						'<div class="content">'.
							'<p class="subject">' . $notification->subject . '</p>'.
							'<p class="body">' . $notification->message . '</p>'.
							'<p class="course">' . $coursesList[$notification->course]->shortname . '</p>'.
							'<p class="time">' . userdate($notification->modified, get_string('strftimerecent')) . '</p>'.
						'</div>'.
					'</div>';
			}

			if(!$hasNotifications) {
				$html.= 
					'<div class="empty">'.
						get_string('empty-notifications','theme_cubic').
					'</div>';
			}
		}

		if(!$records) {
			$html.= '</div>';
		}
	}
	return $html;
}

/**
 * Get current user events menu.
 * @param string $title - menu title.
 * @param int $records - num of records to search for.
 * @return string (HTML)
 */
function bootstrap_get_user_events($records=0) {
	global $DB, $CFG, $OUTPUT;
	
	$title = get_string('menu-events','theme_cubic');
	
	$html = '';
	if(isloggedin() && !isguestuser()) {
		
		if(!$records) {
			$html.= '<div class="title">'.$title.'</div><div class="ajax"></div><div class="container">';
		}
		else {
			$coursesList = bootstrap_get_courses_list();
			$eventsList = bootstrap_get_events_list($records);

			if(count($eventsList) > 0) {
				foreach($eventsList as $event) {
					$date = getdate($event->timestart);

					$html.= 
						'<div class="event">'.
							'<a href="'.$CFG->wwwroot.'/calendar/view.php?view=day&course='.$event->courseid.'&cal_d='.$date['mday'].'&cal_m='.$date['mon'].'&cal_y='.$date['year'].'#event_'.$event->id.'"></a>'.
							'<div class="picture">'.
								'<img src="'.$OUTPUT->pix_url('c/'.$event->eventtype).'" class="icon" />'.
								'<div class="color calendar_event_'.$event->eventtype.'"></div>'.
							'</div>'.

							'<div class="content">'.
								'<p class="name">' . $event->name . '</p>'.
								$event->description;

								if($event->eventtype == 'course') { 
									$html.= '<p class="course">' . $coursesList[$event->courseid]->shortname . '</p>';
								}

								$html.= 
								'<p class="time">'.
									userdate($event->timestart, get_string('strftimerecent')).
								'</p>'.
							'</div>'.
						'</div>';
				}
			}
			else {
				$html.=
					'<div class="empty">'.
						get_string('empty-events','theme_cubic').
					'</div>';
			}
		}

		if(!$records) {
			$html.= 
			'</div>'.
			'<a href="'.$CFG->wwwroot.'/calendar/view.php" class="all">'.
				get_string('see-all','theme_cubic').' '.$title.
			'</a>';
		}
	}
	return $html;
}

/**
 * Get current user messages menu.
 * @param string $title - menu title.
 * @param int $records - num of records to search for.
 * @return string (HTML)
 */
function bootstrap_get_user_messages($records=0) {
	global $DB, $CFG, $USER, $OUTPUT;
	
	$title = get_string('menu-messages','theme_cubic');
	
	$html = '';
	if(isloggedin() && !isguestuser()) {
		
		if(!$records) {
			$html.= '<div class="title">'.$title.'</div><div class="ajax"></div><div class="container">';
		}
		else {
			//Get Unread Messages
			$messagesList = $DB->get_records('message', array('useridto' => $USER->id), 'timecreated desc', 'id, useridfrom, useridto, smallmessage, timecreated', 0, $records);

			if(count($messagesList) < $records) {

				//Get Read Messages
				$readMessages = $DB->get_records('message_read', array('useridto' => $USER->id), 'timecreated desc', 'id, useridfrom, useridto, smallmessage, timecreated', 0, $records-count($messagesList));

				$messagesList = array_merge($messagesList, $readMessages);
			}

			if(count($messagesList) > 0) {
				foreach($messagesList as $message) {
					$from = $DB->get_record('user', array('id' => $message->useridfrom), 'id, firstname, lastname, email, picture, imagealt');

					$html.=
						'<div class="message">'.
							'<a href="'.$CFG->wwwroot.'/message/index.php?viewing=unread&user2='.$message->useridfrom.'" />'.
							'<div class="picture">'.
								$OUTPUT->user_picture($from, array('size' => 50)).
							'</div>'.
							'<div class="content">'.
								'<p class="sender">' . $from->firstname.' '.$from->lastname. '</p>'.
								'<p class="body">' . $message->smallmessage . '</p>'.
								'<p class="time">' . userdate($message->timecreated, get_string('strftimerecent')) . '</p>'.
							'</div>'.
						'</div>';
				}
			}
			else {
				$html.=
					'<div class="empty">'.
						get_string('empty-messages','theme_cubic').
					'</div>';
			}
		}

		if(!$records) {
			$html.= 
				'</div>'.
				'<a href="'.$CFG->wwwroot.'/message/" class="all">'.
					get_string('see-all','theme_cubic').' '.$title.
				'</a>';
		}
	}
	return $html;
}

/**
 * Get system languages menu.
 * @param string $title - menu title.
 * @return string (HTML)
 */
function bootstrap_get_languages() {
	global $OUTPUT;
	
	$title = get_string('menu-languages','theme_cubic');
	$html = 
		'<div class="title">'.$title.'</div>'.
		$OUTPUT->lang_menu().
		'<div class="container"></div>';
	
	return $html;
}

/**
 * Get current user information menu.
 * @return string (HTML)
 */
function bootstrap_get_user_info() {
	global $CFG, $USER, $OUTPUT;
	
	$html = '<table><tr><td>';
				
	if(isloggedin() && !isguestuser()) {
		$html.=
			'<a href="' . $CFG->wwwroot.'/user/view.php?id='.$USER->id . '">'.
				'<h2>' . $USER->firstname.' '.$USER->lastname . '</h2>'.
			'</a>';
			
		if(!empty($USER->email)) {
			$html.= '<p class="email">' . $USER->email . '</p>';
		}
		
		if(!empty($USER->phone2)) {
			$html.= '<p class="phone">' . $USER->phone2 . '</p>';
		}
		
		$social = '';
		if(!empty($USER->url)) {
			$social.= '<a class="website" title="Website" href="'.$USER->url.'" target="_blank"></a>';
		}
		if(!empty($USER->icq)) {
			$social.= '<span class="icq" title="'.$USER->icq.'"></span>';
		}
		if(!empty($USER->skype)) {
			$social.= '<span class="skype" title="'.$USER->skype.'"></span>';
		}
		if(!empty($USER->yahoo)) {
			$social.= '<span class="yahoo" title="'.$USER->yahoo.'"></span>';
		}
		if(!empty($USER->aim)) {
			$social.= '<span class="aim" title="'.$USER->aim.'"></span>';
		}
		if(!empty($USER->msn)) {
			$social.= '<span class="msn" title="'.$USER->msn.'"></span>';
		}
		
		$html.= '<div class="social">'.$social.'</div></td>';
	}
	else {
		$html.=
			'<h2>'.get_string('guest','theme_cubic').'</h2>'.
			'<p class="desc">'.get_string('nouser-info','theme_cubic').'</p>';
	}
	
	$html.= '</td><td>'.bootstrap_get_user_picture(85).'</td></tr></table>';
	
	return $html;
}

/**
 * Get current user settings menu.
 * @param string $title - menu title.
 * @return string (HTML)
 */
function bootstrap_get_user_settings() {
	global $CFG, $USER, $OUTPUT;
	
	$title = get_string('menu-settings','theme_cubic');
	$html = '<div class="title">'.$title.'</div>';
	if(isloggedin() && !isguestuser()) {
		$html.= 
			'<div class="container">'.
				'<div class="setting">'.
					'<a href="'.$CFG->wwwroot.'/user/edit.php?id='.$USER->id.'">'.
						get_string('settings-edit','theme_cubic').
						'<img src="'.$OUTPUT->pix_url('t/edit').'" class="icon" />'.
					'</a>'.
				'</div>'.
				'<div class="setting">'.
					'<a href="'.$CFG->wwwroot.'/login/change_password.php?id=1">'.
						get_string('settings-password','theme_cubic').
						'<img src="'.$OUTPUT->pix_url('i/key').'" class="icon" />'.
					'</a>'.
				'</div>'.
				'<div class="setting">'.
					'<a href="'.$CFG->wwwroot.'/message/edit.php?id='.$USER->id.'">'.
						get_string('settings-msg','theme_cubic').
						'<img src="'.$OUTPUT->pix_url('t/email').'" class="icon" />'.
					'</a>'.
				'</div>'.
				'<div class="setting">'.
					'<a href="'.$CFG->wwwroot.'/login/logout.php?sesskey='.sesskey().'">'.
						get_string('settings-logout','theme_cubic').
						'<img src="'.$OUTPUT->pix_url('a/logout').'" class="icon" />'.
					'</a>'.
				'</div>'.
			'</div>';
	}
	else {
		$html.=
			'<div class="container">'.
				'<div class="setting">'.
					'<a href="'.$CFG->wwwroot.'/login/index.php">'.
						get_string('settings-login','theme_cubic').
						'<img src="'.$OUTPUT->pix_url('a/login').'" class="icon" />'.
					'</a>'.
				'</div>'.
				'<div class="setting">'.
					'<a href="'.$CFG->wwwroot.'/login/forgot_password.php">'.
						get_string('settings-reset','theme_cubic').
						'<img src="'.$OUTPUT->pix_url('i/key').'" class="icon" />'.
					'</a>'.
				'</div>'.
			'</div>';
	}
	
	return $html;
}