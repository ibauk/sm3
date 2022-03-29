<?php


/*
 * I B A U K   -   S C O R E M A S T E R
 *
 * I handle basic maintenance of entrant records
 *
 * I am written for readability rather than efficiency, please keep me that way.
 *
 *
 * Copyright (c) 2020 Bob Stammers
 *
 *
 * This file is part of IBAUK-SCOREMASTER.
 *
 * IBAUK-SCOREMASTER is free software: you can redistribute it and/or modify
 * it under the terms of the MIT License
 *
 * IBAUK-SCOREMASTER is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * MIT License for more details.
 *
 */

session_start();
session_unset();
session_destroy();

require_once('common.php');

if (!rally_params_established()) {
	include "setup.php";
	exit;
}

include "admin.php";
?>
<!DOCTYPE html>
<html>
<head>
<title>ScoreMaster - error</title>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body, input, select		{ font-size: calc(14pt + 1vmin); }
</style>
</head>
<body>
<h1>ScoreMaster not setup properly!</h1>
<p>This installation is not configured properly, I cannot load "<?php echo($main);?>".</p>
<p>Please correct the installation or ask Bob for advice.</p>
</body>
</html>

