<?php

echo "Welcome to the Visualio App setup!\n\n";

// Dotazy na vstupy s výchozími hodnotami
$projectName = readline("Enter project/repo name [default: visu-app]: ");
$projectName = $projectName ?: 'visu-app';
$githubUser = 'visualio';

// Zkontroluj dostupnost názvu repozitáře přes GitHub CLI
$repoUrl = "git@github.com:$githubUser/$projectName.git";
echo "gh repo view $githubUser/$projectName"
exec("gh repo view $githubUser/$projectName --json name 2>&1", $output, $returnCode);

while ($returnCode === 0) {
	echo "\033[31mError: Repository '$projectName' already exists on GitHub.\033[0m\n";
	$projectName = readline("Enter a new project name: ");
	$repoUrl = "git@github.com:$githubUser/$projectName.git";
	exec("gh repo view $githubUser/$projectName --json name 2>&1", $output, $returnCode);
}

echo "\nRepository name '$projectName' is available.\n";

// Pokračování s dalšími dotazy
$phpVersion = readline("PHP version [default: 8.2]: ");
$phpVersion = $phpVersion ?: '8.2';

$nodeVersion = readline("Node.js version [default: 16]: ");
$nodeVersion = $nodeVersion ?: '16';

$httpPort = readline("HTTP port [default: 8080]: ");
$httpPort = $httpPort ?: '8080';

$httpsPort = readline("HTTPS port [default: 8444]: ");
$httpsPort = $httpsPort ?: '8444';

$vitePort = readline("Vite port [default: 3333]: ");
$vitePort = $vitePort ?: '3333';

// Vytvoření obsahu .env
$envContent = <<<EOL
PROJECT_NAME=$projectName
PHP_VERSION=$phpVersion
NODE_VERSION=$nodeVersion
HTTP_PORT=$httpPort
HTTPS_PORT=$httpsPort
VITE_PORT=$vitePort
EOL;

file_put_contents('.env', $envContent);
echo "\n.env file created with the following content:\n";
echo $envContent . "\n";

// Kontrola instalace npm
if (!shell_exec("command -v npm")) {
	echo "\nError: npm is not installed or not in PATH. Please install Node.js before proceeding.\n";
	exit(1);
}

function runCommand(string $command)
{
	$process = proc_open($command, [
		1 => ['pipe', 'w'],
		2 => ['pipe', 'w'],
	], $pipes);

	if (!is_resource($process)) {
		throw new RuntimeException("Failed to execute command: $command");
	}

	while (($line = fgets($pipes[1])) !== false) {
		echo $line;
	}

	while (($error = fgets($pipes[2])) !== false) {
		echo $error;
	}

	fclose($pipes[1]);
	fclose($pipes[2]);

	$exitCode = proc_close($process);
	if ($exitCode !== 0) {
		throw new RuntimeException("Command failed with exit code $exitCode: $command");
	}
}

try {
	// Vytvoření repozitáře přes GitHub CLI
	echo "\nCreating repository on GitHub...\n";
	runCommand("gh repo create $githubUser/$projectName --private --confirm");

	echo "\nRepository '$projectName' created successfully.\n";

	// Nastavení Git repozitáře lokálně
	echo "\nInitializing Git repository...\n";
	runCommand("git init");
	runCommand("git remote add origin git@github.com:$githubUser/$projectName.git");

	// Vytvoření branchí master a develop
	echo "\nCreating branches 'master' and 'develop'...\n";
	runCommand("git checkout -b develop");
	runCommand("git push -u origin develop");

	echo "\nBranches 'master' and 'develop' created successfully.\n";

	// Dotazy na volitelné FTP údaje
	$ftpHostDev = readline("Enter dev FTP host (leave blank to skip): ");
	$ftpUserDev = readline("Enter dev FTP user (leave blank to skip): ");
	$ftpPassDev = readline("Enter dev FTP password (leave blank to skip): ");
	$ftpServerDirDev = readline("Enter dev FTP server directory (leave blank to skip): ");

	// Přidání secrets do GitHub Actions
	echo "\nAdding secrets to GitHub repository...\n";

	if (!empty($ftpHostDev)) {
		runCommand("gh secret set FTP_HOST_DEV --repo $githubUser/$projectName --body \"$ftpHostDev\"");
	}
	if (!empty($ftpUserDev)) {
		runCommand("gh secret set FTP_USERNAME_DEV --repo $githubUser/$projectName --body \"$ftpUserDev\"");
	}
	if (!empty($ftpPassDev)) {
		runCommand("gh secret set FTP_PASSWORD_DEV --repo $githubUser/$projectName --body \"$ftpPassDev\"");
	}
	if (!empty($ftpServerDirDev)) {
		runCommand("gh secret set FTP_SERVER_DIR_DEV --repo $githubUser/$projectName --body \"$ftpServerDirDev\"");
	}

	echo "\nSecrets added successfully.\n";

	echo "\nRunning build script...\n";
	runCommand("composer config process-timeout 600");
	runCommand("npm run ddev:build");
	runCommand("npm run ddev:info");

} catch (RuntimeException $e) {
	echo "\nError: " . $e->getMessage() . "\n";
	exit(1);
}
