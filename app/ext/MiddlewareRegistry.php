<?php
    return [
        'oddMinuteBlock' => function($uri, $method) {
            if (date('i') % 2 === 1) {
                http_response_code(403);
                echo "Blocked by custom middleware.";
                return false;
            }
            return true;
        },
        // Add more middleware here...
    ];