<div id="user-bar">
	<!-- USER LINKS -->
	<div id="user-links">
		<ul>
			<?php if(isloggedin() && !isguestuser()) { ?>
			<li id="notifications" title="<?php echo get_string('menu-notifications','theme_cubic'); ?>">
				<?php echo bootstrap_get_notifications_count(); ?>
				<div class="icon"></div>
			</li>
			
			<li id="events" title="<?php echo get_string('menu-events','theme_cubic'); ?>">
				<?php echo bootstrap_get_events_count(); ?>
				<div class="icon"></div>
			</li>
			
			<li id="messages" title="<?php echo get_string('menu-messages','theme_cubic'); ?>">
				<?php echo bootstrap_get_messages_count(); ?>
				<div class="icon"></div>
			</li>
			<?php } ?>
			<li id="languages" title="<?php echo get_string('menu-languages','theme_cubic'); ?>">
				<div class="flag <?php echo current_language(); ?>"></div>
			</li>
			
			<li id="info" title="<?php echo get_string('menu-info','theme_cubic'); ?>">
				<?php echo bootstrap_get_user_picture(20); ?>
			</li>
			
			<li id="settings" title="<?php echo get_string('menu-settings','theme_cubic'); ?>">
				<div class="icon"></div>
			</li>
		</ul>
	</div>
	
	<!-- BAR MENUS -->
	<div id="menus">
		<?php if(isloggedin() && !isguestuser()) { ?>
		<!-- RIGTH MENUS -->
		<div id="notifications_menu" class="menu">
			<?php echo bootstrap_get_user_notifications(); ?>
		</div>
		
		<div id="events_menu" class="menu">
			<?php echo bootstrap_get_user_events(); ?>
		</div>
		
		<div id="messages_menu" class="menu">
			<?php echo bootstrap_get_user_messages(); ?>
		</div>
		<?php } ?>
		<div id="languages_menu" class="menu">
			<?php echo bootstrap_get_languages(); ?>
		</div>
		
		<div id="info_menu" class="menu">
			<?php echo bootstrap_get_user_info(); ?>
		</div>
		
		<div id="settings_menu" class="menu">
			<?php echo bootstrap_get_user_settings(); ?>
		</div>
	</div>
</div>
