<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
	exit;
}

$nowTs = time();
$lastBootTs = $nowTs - mt_rand(2 * 3600, 18 * 24 * 3600);
$kernelBuildTs = $nowTs - mt_rand(120 * 24 * 3600, 420 * 24 * 3600);
$lastPatchTs = $nowTs - mt_rand(3 * 24 * 3600, 75 * 24 * 3600);

$cpuUsage = mt_rand(10, 50);
$ramTotalGb = mt_rand(16, 128);
$ramUsedGb = mt_rand(4, max(5, $ramTotalGb - 2));
$diskTotalGb = mt_rand(256, 4096);
$diskUsedGb = mt_rand(80, max(81, $diskTotalGb - 40));

$hostSuffix = mt_rand(10, 99);
$uptimeHours = (int)floor(($nowTs - $lastBootTs) / 3600);

$rows = array(
	array('Host Name', 'edge-node-' . $hostSuffix . '.internal.local'),
	array('System Time', gmdate('Y-m-d H:i:s') . ' UTC'),
	array('Last Boot Time', gmdate('Y-m-d H:i:s', $lastBootTs) . ' UTC'),
	array('Uptime', $uptimeHours . ' hours'),
	array('Operating System', 'Linux 6.' . mt_rand(1, 9) . '.' . mt_rand(0, 25) . '-generic'),
	array('Kernel Build Date', gmdate('Y-m-d', $kernelBuildTs)),
	array('CPU Model', 'Intel Xeon Gold 62' . mt_rand(10, 99) . 'R'),
	array('CPU Usage', $cpuUsage . '%'),
	array('CPU Temperature', mt_rand(41, 72) . ' C'),
	array('Memory Usage', $ramUsedGb . ' GB / ' . $ramTotalGb . ' GB'),
	array('Swap Usage', mt_rand(0, 8) . ' GB / 16 GB'),
	array('Disk Usage', $diskUsedGb . ' GB / ' . $diskTotalGb . ' GB'),
	array('Primary Interface', 'eth0'),
	array('Local IP', '10.' . mt_rand(1, 32) . '.' . mt_rand(1, 254) . '.' . mt_rand(1, 254)),
	array('Public IP', mt_rand(52, 104) . '.' . mt_rand(1, 254) . '.' . mt_rand(1, 254) . '.' . mt_rand(1, 254)),
	array('Load Average', mt_rand(0, 2) . '.' . mt_rand(10, 99) . ', ' . mt_rand(0, 2) . '.' . mt_rand(10, 99) . ', ' . mt_rand(0, 2) . '.' . mt_rand(10, 99)),
	array('Running Processes', (string)mt_rand(92, 243)),
	array('Failed Login Attempts (24h)', (string)mt_rand(0, 19)),
	array('Firewall Status', 'Active'),
	array('Last Security Patch', gmdate('Y-m-d H:i:s', $lastPatchTs) . ' UTC'),
	array('Antivirus Signature Version', 'v' . mt_rand(2024, 2026) . '.' . mt_rand(1, 12) . '.' . mt_rand(100, 999)),
	array('Database Cluster Health', 'Nominal'),
	array('Backup Status', 'Completed (' . gmdate('Y-m-d H:i:s', $nowTs - mt_rand(1, 12) * 3600) . ' UTC)'),
);
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>System Information</title>
</head>
<body>
	<h1>System Information</h1>
	<p>Node monitoring snapshot</p>

	<table border="1" cellpadding="6" cellspacing="0" style="width: 100%">
		<thead>
			<tr>
				<th>Metric</th>
				<th>Value</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($rows as $row): ?>
			<tr>
				<td><?php echo esc_html((string)$row[0]); ?></td>
				<td><?php echo esc_html((string)$row[1]); ?></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</body>
</html>
