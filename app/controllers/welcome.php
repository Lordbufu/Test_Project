<?php
// app/controllers/welcome.php
// Example controller for the default landing page.
// This controller renders the 'welcome' view.

use App\Core\App;

// Render the welcome view (see app/views/welcome.php)
App::view('welcome.php');