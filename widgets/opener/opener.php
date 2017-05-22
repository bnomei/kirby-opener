<?php

return [
  'title' => [
    'text'       => 'Kirby Opener License',
    'link'       => false,
    'compressed' => false
  ],
  'html' => function() {
    return tpl::load(__DIR__ . DS . 'opener.html.php', array(
      'text' => '<b>Kirby Opener</b> is running in trial mode. Please support the development of this plugin and <a href="https://bnomei.onfastspring.com/kirby-opener" target="_blank">buy a license</a>. If you already have a license key, please add it to your <code title="site/config/config.php" style="border-bottom: 1px dotted; font-family: monospace;">config.php</code> file.',
    ));
  }
];
