<?php
header('HTTP/1.1 404 Not Found');
$host = $_SERVER['HTTP_HOST'] ?? 'alqadri.gt.tc';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($host) ?> is not available</title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }

  body {
    font-family: -apple-system, 'Segoe UI', Roboto, sans-serif;
    background: #fff;
    color: #202124;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    padding: 20px;
  }

  .container {
    max-width: 560px;
    width: 100%;
  }

  .dino-wrap {
    margin-bottom: 24px;
  }

  /* Chrome dino SVG */
  .dino-wrap svg {
    width: 60px;
    opacity: 0.4;
  }

  h1 {
    font-size: 16px;
    font-weight: 400;
    color: #202124;
    margin-bottom: 16px;
    line-height: 1.5;
  }

  h1 span {
    font-size: 16px;
    font-weight: 600;
  }

  .error-code {
    font-size: 12px;
    color: #70757a;
    margin-bottom: 20px;
    font-family: monospace;
    letter-spacing: 0.5px;
  }

  .suggestions {
    font-size: 13px;
    color: #3c4043;
    margin-bottom: 8px;
    font-weight: 500;
  }

  ul {
    list-style: none;
    padding: 0;
  }

  ul li {
    font-size: 13px;
    color: #3c4043;
    padding: 4px 0 4px 16px;
    position: relative;
    line-height: 1.6;
  }

  ul li::before {
    content: "•";
    position: absolute;
    left: 4px;
    color: #3c4043;
  }

  .details {
    margin-top: 20px;
    font-size: 12px;
    color: #70757a;
  }

  /* Fake address bar on top (optional feel) */
  @media (prefers-color-scheme: dark) {
    body { background: #202124; color: #e8eaed; }
    h1 { color: #e8eaed; }
    .suggestions, ul li { color: #bdc1c6; }
    .error-code { color: #9aa0a6; }
    .details { color: #9aa0a6; }
  }
</style>
</head>
<body>
<div class="container">

  <!-- Chrome Dino icon -->
  <div class="dino-wrap">
    <svg viewBox="0 0 68 68" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M29 4H50V24H44V18H38V12H32V18H26V12H20V18H14V24H8V18H2V42H8V48H14V54H20V60H26V54H32V48H38V42H44V48H50V54H56V48H62V42H68V18H62V12H56V6H50V12H44V6H38V2H32V6H26V2H20V6H14V10H8V4H14V2H20V4H26V2H29Z" fill="#535353"/>
      <path d="M44 24H38V30H44V24Z" fill="white"/>
      <path d="M42 26H40V28H42V26Z" fill="#535353"/>
    </svg>
  </div>

  <h1>This site can't be reached</h1>
  <p style="font-size:14px; color:#3c4043; margin-bottom:12px;">
    <b><?= htmlspecialchars($host) ?></b>'s server DNS address could not be found.
  </p>

  <div class="error-code">DNS_PROBE_FINISHED_NXDOMAIN</div>

  <p class="suggestions">Try:</p>
  <ul>
    <li>Checking the connection</li>
    <li>Checking the proxy and the firewall</li>
    <li>Running Windows Network Diagnostics</li>
  </ul>

  <div class="details">ERR_NAME_NOT_RESOLVED</div>

</div>
</body>
</html>
