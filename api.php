<?php
// debug_api.php
// Script para diagnosticar problemas das APIs

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug - APIs Leonida Brasil</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; }
        .test-section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .code { background: #f8f9fa; padding: 10px; border-radius: 4px; margin: 10px 0; font-family: monospace; }
        .test-btn { background: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
        .test-btn:hover { background: #0056b3; }
        pre { background: #212529; color: #ffffff; padding: 15px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Debug - APIs Leonida Brasil</h1>
        
        <div class="test-section">
            <h2>1. Verificação de Arquivos das APIs</h2>
            <?php
            $api_files = [
                'api/news.php' => 'API de Notícias',
                'api/forum.php' => 'API do Fórum', 
                'api/auth.php' => 'API de Autenticação',
                'api/search.php' => 'API de Busca'
            ];
            
            foreach ($api_files as $file => $description) {
                if (file_exists($file)) {
                    echo "<div class='success'>✅ {$description}: {$file}</div>";
                } else {
                    echo "<div class='error'>❌ {$description}: {$file} - ARQUIVO NÃO ENCONTRADO</div>";
                }
            }
            ?>
        </div>
        
        <div class="test-section">
            <h2>2. Verificação de Dependências</h2>
            <?php
            $dependencies = [
                'config/database.php' => 'Configuração do Banco',
                'utils/helpers.php' => 'Funções Auxiliares',
                'config/constants.php' => 'Constantes'
            ];
            
            foreach ($dependencies as $file => $description) {
                if (file_exists($file)) {
                    echo "<div class='success'>✅ {$description}: {$file}</div>";
                } else {
                    echo "<div class='error'>❌ {$description}: {$file} - ARQUIVO NÃO ENCONTRADO</div>";
                }
            }
            ?>
        </div>
        
        <div class="test-section">
            <h2>3. Teste de Conectividade das APIs</h2>
            <button class="test-btn" onclick="testAPI('news')">Testar API Notícias</button>
            <button class="test-btn" onclick="testAPI('forum')">Testar API Fórum</button>
            <button class="test-btn" onclick="testAPI('auth')">Testar API Auth</button>
            
            <div id="api-results" style="margin-top: 20px;"></div>
        </div>
        
        <div class="test-section">
            <h2>4. Configuração do JavaScript</h2>
            <p>Verifique se o JavaScript está configurado corretamente:</p>
            <div class="code">
                <strong>Base URL atual detectada:</strong> 
                <span id="current-base-url"></span>
            </div>
            
            <button class="test-btn" onclick="checkJSConfig()">Verificar Config JS</button>
            <div id="js-config-results"></div>
        </div>
        
        <div class="test-section">
            <h2>5. URLs de Teste Direto</h2>
            <p>Teste as APIs diretamente:</p>
            <?php
            $base_url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
            $test_urls = [
                'news' => "{$base_url}/api/news.php?page=1",
                'forum' => "{$base_url}/api/forum.php?page=1", 
                'auth' => "{$base_url}/api/auth.php"
            ];
            
            foreach ($test_urls as $name => $url) {
                echo "<div><strong>{$name}:</strong> <a href='{$url}' target='_blank'>{$url}</a></div>";
            }
            ?>
        </div>
        
        <div class="test-section">
            <h2>6. Configuração .htaccess</h2>
            <?php
            if (file_exists('.htaccess')) {
                echo "<div class='success'>✅ Arquivo .htaccess encontrado</div>";
                
                // Verificar se mod_rewrite está funcionando
                $rewrite_test = file_get_contents(__DIR__ . '/.htaccess');
                if (strpos($rewrite_test, 'RewriteEngine On') !== false) {
                    echo "<div class='success'>✅ RewriteEngine ativado no .htaccess</div>";
                } else {
                    echo "<div class='warning'>⚠️ RewriteEngine pode não estar configurado</div>";
                }
            } else {
                echo "<div class='error'>❌ Arquivo .htaccess não encontrado</div>";
            }
            ?>
        </div>
        
        <div class="test-section">
            <h2>7. Correção Automática</h2>
            <p>Se os testes falharem, use estas correções:</p>
            
            <h3>Problema 1: APIs não encontradas</h3>
            <div class="code">
                Certifique-se que a pasta api/ existe e contém os arquivos:
                - api/news.php
                - api/forum.php  
                - api/auth.php
                - api/search.php
            </div>
            
            <h3>Problema 2: Erro de conexão</h3>
            <div class="code">
                1. Verifique config/database.php<br>
                2. Teste conexão com banco de dados<br>
                3. Verifique permissões de arquivo
            </div>
            
            <h3>Problema 3: JavaScript não encontra APIs</h3>
            <div class="code">
                Verifique se CONFIG.api.baseUrl está correto no principal.js
            </div>
            
            <button class="test-btn" onclick="generateFixedConfig()">Gerar Config Corrigida</button>
            <div id="fixed-config"></div>
        </div>
    </div>
    
    <script>
        // Detectar URL base atual
        document.getElementById('current-base-url').textContent = window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '');
        
        async function testAPI(apiName) {
            const resultsDiv = document.getElementById('api-results');
            resultsDiv.innerHTML = `<div>Testando API ${apiName}...</div>`;
            
            const baseUrl = window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '');
            const urls = {
                'news': `${baseUrl}/api/news.php?page=1`,
                'forum': `${baseUrl}/api/forum.php?page=1`,
                'auth': `${baseUrl}/api/auth.php`
            };
            
            try {
                const response = await fetch(urls[apiName]);
                const data = await response.json();
                
                resultsDiv.innerHTML = `
                    <div class="success">✅ API ${apiName} funcionando!</div>
                    <pre>${JSON.stringify(data, null, 2)}</pre>
                `;
            } catch (error) {
                resultsDiv.innerHTML = `
                    <div class="error">❌ Erro na API ${apiName}: ${error.message}</div>
                    <div>URL testada: ${urls[apiName]}</div>
                `;
            }
        }
        
        function checkJSConfig() {
            const resultsDiv = document.getElementById('js-config-results');
            const baseUrl = window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '');
            
            // Verificar se o principal.js está carregado
            if (typeof window.LeonidaBrasil !== 'undefined') {
                resultsDiv.innerHTML = `
                    <div class="success">✅ JavaScript carregado corretamente</div>
                    <div>Base URL configurada: ${window.LeonidaBrasil.config.api.baseUrl}</div>
                `;
            } else {
                resultsDiv.innerHTML = `
                    <div class="error">❌ JavaScript não carregado ou CONFIG não encontrado</div>
                    <div>URL base deveria ser: ${baseUrl}</div>
                `;
            }
        }
        
        function generateFixedConfig() {
            const resultsDiv = document.getElementById('fixed-config');
            const baseUrl = window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '');
            
            resultsDiv.innerHTML = `
                <h4>Configuração Corrigida para principal.js:</h4>
                <pre>
const CONFIG = {
    api: {
        baseUrl: '${baseUrl}',
        endpoints: {
            news: '/api/news.php',
            forum: '/api/forum.php',
            login: '/api/auth.php',
            search: '/api/search.php'
        }
    },
    // ... resto da configuração
};
                </pre>
            `;
        }
    </script>
</body>
</html>