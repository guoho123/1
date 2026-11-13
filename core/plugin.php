<?php
if (!class_exists('PluginManager')) {
class PluginManager {
    private $pluginsDir;
    private $dataDir;
    private $hooks = [];
    private $apiRoutes = [];
    private $installedPlugins = [];

    public function __construct() {
        $this->pluginsDir = dirname(__DIR__) . '/plugins/';
        $this->dataDir = dirname(__DIR__) . '/data/';
        if (!file_exists($this->pluginsDir)) mkdir($this->pluginsDir, 0777, true);
        if (!file_exists($this->dataDir)) mkdir($this->dataDir, 0777, true);
        $this->loadInstalledPlugins();
    }

    private function loadInstalledPlugins() {
        $pluginsFile = $this->dataDir . 'plugins.json';
        if (file_exists($pluginsFile)) {
            $this->installedPlugins = json_decode(file_get_contents($pluginsFile), true) ?: [];
        }
    }

    private function saveInstalledPlugins() {
        $pluginsFile = $this->dataDir . 'plugins.json';
        file_put_contents($pluginsFile, json_encode($this->installedPlugins, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    public function installPlugin($zipPath) {
        if (!file_exists($zipPath)) {
            return ['code' => 400, 'msg' => '压缩包不存在'];
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return ['code' => 400, 'msg' => '压缩包解压失败'];
        }

        $firstFile = $zip->getNameIndex(0);
        // 提取顶层目录名（处理 my-plugin/plugin.json 或 plugin.json 两种情况）
        $parts = explode('/', trim($firstFile, '/'));
        $pluginName = $parts[0];
        $pluginDir = $this->pluginsDir . $pluginName . '/';

        // 如果提取的第一个条目不是目录（即zip没有外层文件夹），创建新目录
        if (strpos($firstFile, '/') === false) {
            // plugin.json直接在zip根目录，需要新建目录
            $pluginName = 'plugin_' . time();
            $pluginDir = $this->pluginsDir . $pluginName . '/';
            mkdir($pluginDir, 0777, true);
        }

        if (file_exists($pluginDir)) {
            $zip->close();
            return ['code' => 400, 'msg' => '插件已存在'];
        }

        if (!$zip->extractTo($this->pluginsDir)) {
            $zip->close();
            return ['code' => 400, 'msg' => '解压失败'];
        }
        $zip->close();

        $pluginJson = $pluginDir . 'plugin.json';
        if (!file_exists($pluginJson)) {
            $this->deleteDir($pluginDir);
            return ['code' => 400, 'msg' => '缺少plugin.json配置文件'];
        }

        $config = json_decode(file_get_contents($pluginJson), true);
        if (!$config || !isset($config['name'], $config['version'], $config['entry'])) {
            $this->deleteDir($pluginDir);
            return ['code' => 400, 'msg' => 'plugin.json格式错误'];
        }

        if (isset($this->installedPlugins[$config['name']])) {
            $this->deleteDir($pluginDir);
            return ['code' => 400, 'msg' => '插件名称已存在'];
        }

        $this->installedPlugins[$config['name']] = [
            'name' => $config['name'],
            'title' => $config['title'] ?? $config['name'],
            'description' => $config['description'] ?? '',
            'version' => $config['version'],
            'enabled' => $config['enabled'] ?? true,
            'entry' => $config['entry'],
            'settings_url' => $config['settings_url'] ?? '',
            'hooks' => $config['hooks'] ?? []
        ];

        if (file_exists($pluginDir . 'install.php')) {
            include $pluginDir . 'install.php';
        }

        if ($this->installedPlugins[$config['name']]['enabled']) {
            $this->enablePlugin($config['name'], false);
        }

        $this->saveInstalledPlugins();
        return ['code' => 200, 'msg' => '安装成功'];
    }

    public function enablePlugin($pluginName, $save = true) {
        if (!isset($this->installedPlugins[$pluginName])) {
            return ['code' => 400, 'msg' => '插件不存在'];
        }

        $pluginDir = $this->pluginsDir . $pluginName . '/';
        $pluginJson = $pluginDir . 'plugin.json';
        $config = json_decode(file_get_contents($pluginJson), true);

        if (file_exists($pluginDir . 'enable.php')) {
            include $pluginDir . 'enable.php';
        }

        $entryFile = $pluginDir . $config['entry'];
        if (file_exists($entryFile)) {
            include_once $entryFile;
        }

        $this->installedPlugins[$pluginName]['enabled'] = true;
        if ($save) {
            $this->saveInstalledPlugins();
        }

        return ['code' => 200, 'msg' => '启用成功'];
    }

    public function disablePlugin($pluginName) {
        if (!isset($this->installedPlugins[$pluginName])) {
            return ['code' => 400, 'msg' => '插件不存在'];
        }

        $pluginDir = $this->pluginsDir . $pluginName . '/';
        if (file_exists($pluginDir . 'disable.php')) {
            include $pluginDir . 'disable.php';
        }

        $this->installedPlugins[$pluginName]['enabled'] = false;
        $this->saveInstalledPlugins();
        return ['code' => 200, 'msg' => '禁用成功'];
    }

    public function updatePlugin($pluginName, $zipPath) {
        if (!isset($this->installedPlugins[$pluginName])) {
            return ['code' => 400, 'msg' => '插件不存在'];
        }

        if (!file_exists($zipPath)) {
            return ['code' => 400, 'msg' => '压缩包不存在'];
        }

        $oldVersion = $this->installedPlugins[$pluginName]['version'];
        $pluginDir = $this->pluginsDir . $pluginName . '/';

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return ['code' => 400, 'msg' => '压缩包解压失败'];
        }

        $tempDir = $this->dataDir . 'temp_update_' . time() . '/';
        mkdir($tempDir, 0777, true);
        $zip->extractTo($tempDir);
        $zip->close();

        $tempPluginDir = $tempDir . $pluginName . '/';
        if (!file_exists($tempPluginDir)) {
            $tempDirItems = scandir($tempDir);
            foreach ($tempDirItems as $item) {
                if ($item !== '.' && $item !== '..') {
                    $tempPluginDir = $tempDir . $item . '/';
                    break;
                }
            }
        }

        $pluginJson = $tempPluginDir . 'plugin.json';
        if (!file_exists($pluginJson)) {
            $this->deleteDir($tempDir);
            return ['code' => 400, 'msg' => '缺少plugin.json配置文件'];
        }

        $config = json_decode(file_get_contents($pluginJson), true);
        if (!$config || $config['name'] !== $pluginName) {
            $this->deleteDir($tempDir);
            return ['code' => 400, 'msg' => '插件名称不匹配'];
        }

        if ($this->versionCompare($config['version'], $oldVersion) <= 0) {
            $this->deleteDir($tempDir);
            return ['code' => 400, 'msg' => '新版本号必须高于当前版本'];
        }

        $dataDirs = ['data', 'config', 'user_data'];
        foreach ($dataDirs as $dataDir) {
            $source = $pluginDir . $dataDir . '/';
            $dest = $tempPluginDir . $dataDir . '/';
            if (file_exists($source) && is_dir($source)) {
                if (!file_exists($dest)) mkdir($dest, 0777, true);
                $this->copyDir($source, $dest);
            }
        }

        $backupDir = $this->dataDir . 'backup_' . $pluginName . '_' . time() . '/';
        mkdir($backupDir, 0777, true);
        $this->copyDir($pluginDir, $backupDir);

        if ($this->installedPlugins[$pluginName]['enabled']) {
            if (file_exists($pluginDir . 'disable.php')) {
                include $pluginDir . 'disable.php';
            }
        }

        $this->deleteDir($pluginDir);
        if (!rename($tempPluginDir, $pluginDir)) {
            $this->copyDir($backupDir, $pluginDir);
            $this->deleteDir($tempDir);
            return ['code' => 500, 'msg' => '更新失败：无法移动插件文件'];
        }
        $this->deleteDir($tempDir);

        if (file_exists($pluginDir . 'update.php')) {
            include $pluginDir . 'update.php';
        }

        if ($this->installedPlugins[$pluginName]['enabled']) {
            if (file_exists($pluginDir . 'enable.php')) {
                include_once $pluginDir . 'enable.php';
            }
            $entryFile = $pluginDir . $config['entry'];
            if (file_exists($entryFile)) {
                include_once $entryFile;
            }
        }

        $this->installedPlugins[$pluginName]['version'] = $config['version'];
        $this->installedPlugins[$pluginName]['title'] = $config['title'] ?? $pluginName;
        $this->installedPlugins[$pluginName]['description'] = $config['description'] ?? '';
        $this->installedPlugins[$pluginName]['entry'] = $config['entry'];
        $this->installedPlugins[$pluginName]['settings_url'] = $config['settings_url'] ?? '';
        $this->installedPlugins[$pluginName]['hooks'] = $config['hooks'] ?? [];

        $this->saveInstalledPlugins();
        return ['code' => 200, 'msg' => '更新成功'];
    }

    public function uninstallPlugin($pluginName) {
        if (!isset($this->installedPlugins[$pluginName])) {
            return ['code' => 400, 'msg' => '插件不存在'];
        }

        $pluginDir = $this->pluginsDir . $pluginName . '/';

        if ($this->installedPlugins[$pluginName]['enabled']) {
            if (file_exists($pluginDir . 'disable.php')) {
                include $pluginDir . 'disable.php';
            }
        }

        if (file_exists($pluginDir . 'uninstall.php')) {
            include $pluginDir . 'uninstall.php';
        }

        $this->deleteDir($pluginDir);
        unset($this->installedPlugins[$pluginName]);
        $this->saveInstalledPlugins();
        return ['code' => 200, 'msg' => '卸载成功'];
    }

    public function getPlugins() {
        return array_values($this->installedPlugins);
    }

    public function registerHook($hookName, $callback) {
        if (!isset($this->hooks[$hookName])) {
            $this->hooks[$hookName] = [];
        }
        $this->hooks[$hookName][] = $callback;
    }

    public function triggerHook($hookName, $data = null) {
        if (!isset($this->hooks[$hookName])) {
            return $data;
        }
        foreach ($this->hooks[$hookName] as $callback) {
            $result = call_user_func($callback, $data);
            if ($result !== null) {
                $data = $result;
            }
        }
        return $data;
    }

    public function registerApiRoute($route, $callback) {
        $this->apiRoutes[$route] = $callback;
    }

    public function processApiRoute($route, $params = []) {
        if (isset($this->apiRoutes[$route])) {
            return call_user_func($this->apiRoutes[$route], $params);
        }
        return ['code' => 404, 'msg' => 'API路由不存在'];
    }

    public function getApiRoutes() {
        return array_keys($this->apiRoutes);
    }

    private function versionCompare($v1, $v2) {
        $parts1 = explode('.', $v1);
        $parts2 = explode('.', $v2);
        $max = max(count($parts1), count($parts2));
        for ($i = 0; $i < $max; $i++) {
            $p1 = isset($parts1[$i]) ? (int)$parts1[$i] : 0;
            $p2 = isset($parts2[$i]) ? (int)$parts2[$i] : 0;
            if ($p1 > $p2) return 1;
            if ($p1 < $p2) return -1;
        }
        return 0;
    }

    private function deleteDir($dir) {
        if (!file_exists($dir)) return true;
        $dir = rtrim($dir, '/\\') . '/';
        if (is_dir($dir)) {
            $items = scandir($dir);
            if ($items === false) return false;
            $files = array_diff($items, ['.', '..']);
            foreach ($files as $file) {
                $path = $dir . $file;
                if (is_dir($path)) {
                    $this->deleteDir($path);
                } else {
                    @chmod($path, 0777);
                    @unlink($path);
                }
            }
            @chmod($dir, 0777);
            return @rmdir($dir);
        } else {
            @chmod($dir, 0777);
            return @unlink($dir);
        }
    }

    private function copyDir($source, $dest) {
        if (!file_exists($dest)) mkdir($dest, 0777, true);
        $items = scandir($source);
        if ($items === false) return;
        $files = array_diff($items, ['.', '..']);
        foreach ($files as $file) {
            $sourcePath = $source . $file;
            $destPath = $dest . $file;
            if (is_dir($sourcePath)) {
                $this->copyDir($sourcePath, $destPath);
            } else {
                copy($sourcePath, $destPath);
            }
        }
    }

    public function loadEnabledPlugins() {
        foreach ($this->installedPlugins as $pluginName => $plugin) {
            if ($plugin['enabled']) {
                $pluginDir = $this->pluginsDir . $pluginName . '/';
                $entryFile = $pluginDir . $plugin['entry'];
                if (file_exists($entryFile)) {
                    include_once $entryFile;
                }
            }
        }
    }
}

}

if (!isset($pluginManager)) {
    $pluginManager = new PluginManager();
    $pluginManager->loadEnabledPlugins();
}