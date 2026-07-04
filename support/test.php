<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "This script can be run only from CLI.\n");
	exit(1);
}

const ANSI_RESET = "\033[0m";
const ANSI_BOLD = "\033[1m";
const ANSI_RED = "\033[31m";
const ANSI_GREEN = "\033[32m";
const ANSI_YELLOW = "\033[33m";
const ANSI_BLUE = "\033[34m";
const ANSI_CYAN = "\033[36m";

const LIMIT_ARG_PREFIX = '--limit=';
const AUTO_BATCH_ARG_PREFIX = '--auto-batch';
const NO_DELAY_ARG_PREFIX = '--no-delay';
const LEGIT_REQUESTS_ARG = '--legit-requests';
const DEFAULT_LEGIT_REQUEST_LIMIT = 50;

const BROWSER_USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36';

define('BOTS_FILE_PATH', 
	realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR 
		. 'data' . DIRECTORY_SEPARATOR 
		. 'bots.json');

function colorText(string $text, string $color): string {
	return $color . $text . ANSI_RESET;
}

function displayError(string $text, string $color = ANSI_RED): void {
	fwrite(STDERR, colorText($text, $color));
}

function printUsage(): void {
	$usage = <<<TXT
Usage:
  	php test.php <url> [--limit=NUMBER] [--auto-batch] [--no-delay]

Parameters:
	<url>            Required. Full target URL (for example: https://example.com/article).
	--limit=NUMBER   Optional. Max number of scenarios to run, starting from the beginning.
					 	Use 0 (default) to run all scenarios from bots.json.
	--auto-batch     Optional. Skip confirmation prompt between batches.
	--no-delay       Optional. Disable random delay (1-2s) between requests.
	--legit-requests Optional. Emit legitimate-looking requests. If no positive --limit specified, 50 will be issued. The remainder of the parameters still apply.

Examples:
  	php test.php "https://example.com/article" --limit=25
	php test.php "https://example.com/article" --auto-batch --no-delay
	php test.php "https://example.com/article" --legit-requests --limit=50
TXT;
	echo $usage . PHP_EOL;
}

function getLegitimateUserAgents(): array {
	return [
		"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36",
		"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36",
		"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36",
		"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36",
		"Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36",
		"Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36",
		"Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:128.0) Gecko/20100101 Firefox/128.0",
		"Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:141.0) Gecko/20100101 Firefox/141.0",
		"Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:128.0) Gecko/20100101 Firefox/128.0",
		"Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:141.0) Gecko/20100101 Firefox/141.0",
		"Mozilla/5.0 (X11; Linux x86_64; rv:128.0) Gecko/20100101 Firefox/128.0",
		"Mozilla/5.0 (X11; Linux x86_64; rv:141.0) Gecko/20100101 Firefox/141.0",
		"Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:128.0) Gecko/20100101 Firefox/128.0",
		"Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:141.0) Gecko/20100101 Firefox/141.0",
		"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Safari/605.1.15",
		"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0"
	];
}

function pickLegitimateUserAgent(): string {
	static $userAgents = null;
	if ($userAgents === null) {
		$userAgents = getLegitimateUserAgents();
	}

	$key = array_rand($userAgents, 1);
	return $userAgents[$key];
}

function pickRandomValue(array $values): mixed {
	$key = array_rand($values, 1);
	return $values[$key];
}

function getLegitimateAcceptHeaders(): array {
	return [
		'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
		'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
		'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
	];
}

function getLegitimateAcceptLanguageHeaders(): array {
	return [
		'en-US,en;q=0.9',
		'en-GB,en;q=0.9',
		'ro-RO,ro;q=0.9,en-US;q=0.8,en;q=0.7',
		'de-DE,de;q=0.9,en-US;q=0.8,en;q=0.7',
		'fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7',
	];
}

function getLegitimatePlainReferers(): array {
	return [
		'https://www.facebook.com/',
		'https://www.linkedin.com/',
		'https://x.com/',
		'https://www.reddit.com/',
		'https://news.ycombinator.com/',
		'https://www.youtube.com/',
	];
}

function getSearchEngineReferers(string $keyword): array {
	$encodedKeyword = rawurlencode($keyword);

	return [
		'https://www.google.com/search?q=' . $encodedKeyword,
		'https://www.bing.com/search?q=' . $encodedKeyword,
		'https://duckduckgo.com/?q=' . $encodedKeyword,
		'https://search.yahoo.com/search?p=' . $encodedKeyword,
	];
}

function getSearchKeywords(): array {
	return [
		'wordpress post views',
		'wp postviews analytics',
		'how to filter bot traffic',
		'wordpress plugin bot detection',
		'article statistics wordpress',
		'alexboia.net wordpress',
	];
}

function getCloudflareRequestMetadata(): array {
	return [
		['country' => 'RO', 'asnName' => 'RCS & RDS SA'],
		['country' => 'US', 'asnName' => 'Comcast Cable Communications LLC'],
		['country' => 'GB', 'asnName' => 'British Telecommunications PLC'],
		['country' => 'DE', 'asnName' => 'Deutsche Telekom AG'],
		['country' => 'FR', 'asnName' => 'Orange S.A.'],
		['country' => 'NL', 'asnName' => 'KPN B.V.'],
		['country' => 'ES', 'asnName' => 'Telefonica de Espana'],
	];
}

function buildLegitimateHeaders(): array {
	$headers = [
		'Accept: ' . pickRandomValue(getLegitimateAcceptHeaders()),
		'Accept-Language: ' . pickRandomValue(getLegitimateAcceptLanguageHeaders()),
	];

	if (random_int(1, 100) <= 35) {
		$metadata = pickRandomValue(getCloudflareRequestMetadata());
		$headers[] = 'CF-IPCountry: ' . $metadata['country'];
		$headers[] = 'CF-Asn-Name: ' . $metadata['asnName'];
	}

	return $headers;
}

function buildLegitimateReferer(): string {
	$mode = random_int(1, 100);

	if ($mode <= 35) {
		return '';
	}

	if ($mode <= 65) {
		return pickRandomValue(getLegitimatePlainReferers());
	}

	$keyword = pickRandomValue(getSearchKeywords());
	return pickRandomValue(getSearchEngineReferers($keyword));
}

function normalizeStringList(array $items): array {
	$list = array_values(array_filter(array_map(
		static function ($item): string {
			return is_string($item) ? trim($item) : '';
		},
		$items
	), static function (string $item): bool {
		return $item !== '';
	}));

	return array_values(array_unique($list));
}

function readBotsAndReferers(string $botsFilePath): array {
	if (!file_exists($botsFilePath) || !is_readable($botsFilePath)) {
		displayError("The bots.json file is missing or not readable: {$botsFilePath}\n");
		exit(1);
	}

	$content = file_get_contents($botsFilePath);
	if ($content === false || trim($content) === '') {
		displayError("The bots.json file is empty or could not be read.\n");
		exit(1);
	}

	$decoded = json_decode($content, true);
	if (!is_array($decoded) || json_last_error() !== JSON_ERROR_NONE) {
		displayError("bots.json is invalid JSON.\n");
		exit(1);
	}

	if (!isset($decoded['bots']) || !is_array($decoded['bots'])) {
		displayError("bots.json does not contain a valid 'bots' array.\n");
		exit(1);
	}

	$bots = normalizeStringList($decoded['bots']);
	$referers = isset($decoded['referers']) && is_array($decoded['referers'])
		? normalizeStringList($decoded['referers'])
		: [];

	if (empty($bots)) {
		displayError("No bot user agents found in bots.json.\n");
		exit(1);
	}

	if (empty($referers)) {
		echo colorText("Warning: no referers found in bots.json. Referer tests will be skipped.\n", ANSI_YELLOW);
	}

	return [
		'bots' => $bots,
		'referers' => $referers,
	];
}

function shouldContinue(): bool {
	echo colorText("Continue with next batch? [Y/n]: ", ANSI_YELLOW);
	$answer = fgets(STDIN);
	if ($answer === false) {
		return false;
	}

	$answer = strtolower(trim($answer));
	return $answer === '' 
		|| $answer === 'y' 
		|| $answer === 'yes' 
		|| $answer === 'da';
}

function normalizeRefererToUrl(string $value): string {
	$value = trim($value);
	if ($value === '') {
		return '';
	}

	if (filter_var($value, FILTER_VALIDATE_URL) !== false) {
		return $value;
	}

	$value = preg_replace('#^https?://#i', '', $value);
	if (!is_string($value) || $value === '') {
		return '';
	}

	return 'https://' . ltrim($value, '/');
}

function makeRequest(string $url, string $userAgent, string $referer = '', array $extraHeaders = []): array {
	$ch = curl_init();
	$headers = [];

	if ($referer !== '') {
		$headers[] = 'Referer: ' . $referer;
	}

	foreach ($extraHeaders as $header) {
		if (is_string($header) && trim($header) !== '') {
			$headers[] = trim($header);
		}
	}

	curl_setopt_array($ch, [
		CURLOPT_URL => $url,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_HEADER => false,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_MAXREDIRS => 5,
		CURLOPT_TIMEOUT => 20,
		CURLOPT_CONNECTTIMEOUT => 10,
		CURLOPT_USERAGENT => $userAgent,
		CURLOPT_HTTPHEADER => $headers,
	]);

	$body = curl_exec($ch);
	$error = curl_error($ch);
	$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$finalUrl = (string)curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
	$totalTime = (float)curl_getinfo($ch, CURLINFO_TOTAL_TIME);

	return [
		'body' => $body,
		'error' => $error,
		'httpCode' => $httpCode,
		'finalUrl' => $finalUrl,
		'totalTime' => $totalTime,
	];
}

function buildScenarios(array $bots, array $referers): array {
	$scenarios = [];

	foreach ($bots as $botUserAgent) {
		$scenarios[] = [
			'type' => 'UA',
			'label' => $botUserAgent,
			'userAgent' => $botUserAgent,
			'referer' => '',
			'headers' => [],
		];
	}

	foreach ($referers as $refererValue) {
		$normalizedReferer = normalizeRefererToUrl($refererValue);
		if ($normalizedReferer === '') {
			continue;
		}

		$scenarios[] = [
			'type' => 'REF',
			'label' => $refererValue,
			'userAgent' => BROWSER_USER_AGENT,
			'referer' => $normalizedReferer,
			'headers' => [],
		];
	}

	return $scenarios;
}

function buildLegitimateScenarios(int $count): array {
	$scenarios = [];

	for ($i = 0; $i < $count; $i++) {
		$referer = buildLegitimateReferer();
		$label = $referer !== ''
			? 'Browser request with referer'
			: 'Direct browser request';

		$scenarios[] = [
			'type' => 'LEGIT',
			'label' => $label,
			'userAgent' => pickLegitimateUserAgent(),
			'referer' => $referer,
			'headers' => buildLegitimateHeaders(),
		];
	}

	return $scenarios;
}

function sleepIfNeeded(bool $noDelay, int $current, int $total): void {
	if ($noDelay) {
		echo colorText("No delay selected. Moving on...\n\n", ANSI_YELLOW);
		return;
	}

	if ($current < $total) {
		$delayMicroseconds = random_int(1000000, 2000000);
		$message = sprintf("  Sleeping %.2fs...\n\n", $delayMicroseconds / 1000000);
		echo colorText($message, ANSI_YELLOW);
		usleep($delayMicroseconds);
	}
}

function displayRunningScenarioInfo(array $scenario, int $current, int $total): void {
	$header = sprintf('[%d/%d] %s: %s', 
		$current, 
		$total, 
		$scenario['type'], 
		$scenario['label']);
	
	echo colorText($header . "\n", ANSI_CYAN);
	if ($scenario['referer'] !== '') {
		echo colorText("  Referer header: {$scenario['referer']}\n", ANSI_BLUE);
	}
	echo colorText("  User-Agent: {$scenario['userAgent']}\n", ANSI_BLUE);

	$headers = isset($scenario['headers']) && is_array($scenario['headers'])
		? $scenario['headers']
		: [];

	foreach ($headers as $header) {
		echo colorText("  Header: {$header}\n", ANSI_BLUE);
	}
}

function displayBanner(string $targetUrl, int $uaCount, int $refererCount, int $total): void {
	echo colorText(ANSI_BOLD . "ABNet Bot Request Tester" . ANSI_RESET . "\n", ANSI_CYAN);
	echo colorText("Target URL: {$targetUrl}\n", ANSI_BLUE);
	echo colorText("UA scenarios: {$uaCount} | Referer scenarios: {$refererCount}\n", ANSI_BLUE);
	echo colorText("Total requests planned: {$total}\n\n", ANSI_BLUE);
}

function displaySummary(int $successCount, int $errorCount): void {
	echo colorText("Summary\n", ANSI_CYAN);
	echo colorText("  Success: {$successCount}\n", ANSI_GREEN);
	echo colorText("  Errors: {$errorCount}\n", ($errorCount > 0 ? ANSI_RED : ANSI_GREEN));
}

$args = $argv;
array_shift($args);

if (empty($args)) {
	printUsage();
	exit(1);
}

$url = '';
$limit = 0;
$limitSpecified = false;
$autoBatch = false;
$noDelay = false;
$legitRequests = false;

foreach ($args as $arg) {
	if (strpos($arg, LIMIT_ARG_PREFIX) === 0) {
		$limitValue = substr($arg, 
			strlen(LIMIT_ARG_PREFIX));

		$limit = max(0, (int)$limitValue);
		$limitSpecified = true;
		continue;
	}

	if (strpos($arg, AUTO_BATCH_ARG_PREFIX) === 0) {
		$autoBatch = true;
		continue;
	}

	if (strpos($arg, NO_DELAY_ARG_PREFIX) === 0) {
		$noDelay = true;
		continue;
	}

	if ($arg === LEGIT_REQUESTS_ARG) {
		$legitRequests = true;
		continue;
	}

	if ($url === '') {
		$url = trim($arg);
	}
}

if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
	displayError("Please provide a valid URL.\n");
	printUsage();
	exit(1);
}

if (!function_exists('curl_init')) {
	displayError("PHP cURL extension is required.\n");
	exit(1);
}

$bots = [];
$referers = [];

if (!$legitRequests) {
	$sources = readBotsAndReferers(BOTS_FILE_PATH);
	$bots = $sources['bots'];
	$referers = $sources['referers'];

	$scenarios = buildScenarios($bots, $referers);

	if ($limit > 0) {
		$scenarios = array_slice($scenarios, 0, $limit);
	}
} else {
	$legitRequestsCount = $limitSpecified && $limit > 0
		? $limit
		: DEFAULT_LEGIT_REQUEST_LIMIT;

	$scenarios = buildLegitimateScenarios($legitRequestsCount);	
}

if (empty($scenarios)) {
	displayError("No scenarios available to test.\n");
	exit(1);
}

$total = count($scenarios);
$successCount = 0;
$errorCount = 0;
$uaCount = count($bots);
$refererCount = count($referers);

displayBanner($url, $uaCount, $refererCount, $total);

foreach ($scenarios as $index => $scenario) {
	$current = $index + 1;

	displayRunningScenarioInfo($scenario, 
		$current, 
		$total);

	$result = makeRequest($url, 
		$scenario['userAgent'], 
		$scenario['referer'],
		isset($scenario['headers']) && is_array($scenario['headers'])
			? $scenario['headers']
			: []);

	if ($result['error'] !== '') {
		$errorCount++;
		displayError("  ERROR: {$result['error']}\n");
	} else {
		$successCount++;
		$statusColor = ($result['httpCode'] >= 200 && $result['httpCode'] < 400) 
			? ANSI_GREEN 
			: ANSI_YELLOW;

		echo colorText(sprintf("  HTTP: %d | Time: %.2fs\n", 
				$result['httpCode'], 
				$result['totalTime']), 
			$statusColor);
		echo colorText("  Final URL: {$result['finalUrl']}\n", ANSI_BLUE);
	}

	echo PHP_EOL;

	if (!$autoBatch && $current % 10 === 0 && $current < $total) {
		if (!shouldContinue()) {
			echo colorText("Stopped by user after {$current} requests.\n", ANSI_YELLOW);
			break;
		}
	}

	sleepIfNeeded($noDelay, $current, $total);
}

displaySummary($successCount, $errorCount);
