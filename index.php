<?php

// Caminho da pasta local (ajuste para o seu sistema)
$directory = '';

/** 
 * Lista a estrutura de diretórios em árvore e exibe links de download
 *
 * @param string $dir Diretório inicial para listar
 * @param int $indent Nível de indentação para formatação em árvore
 */
function listDirectoryTree($dir, $indent = 0)
{
    if (is_dir($dir)) {
        $files = scandir($dir);

        // Exibe a pasta principal com link para baixar todos os arquivos, se tiver arquivos
        echo str_repeat("&nbsp;&nbsp;", $indent) . "📁 " . basename($dir) . " (pasta)";
        if (hasFilesInDirectory($files, $dir)) {
            echo " - <a href='?download_all=" . urlencode($dir) . "'>Baixar todos os arquivos</a><br>";
        } else {
            echo "<br>";
        }

        // Exibe cada arquivo ou subdiretório
        foreach ($files as $file) {
            if ($file !== "." && $file !== "..") {
                $path = $dir . DIRECTORY_SEPARATOR . $file;

                if (is_dir($path)) {
                    echo str_repeat("&nbsp;&nbsp;", $indent + 1) . "📁 " . basename($file) . "<br>";
                    listDirectoryTree($path, $indent + 2); // Chamada recursiva para subpastas
                } else {
                    echo str_repeat("&nbsp;&nbsp;", $indent + 1) . "📄 " . basename($file) .
                        " - <a href='?download_file=" . urlencode($path) . "'>Baixar</a><br>";
                }
            }
        }
    } else {
        echo "Erro: O diretório especificado não existe.<br>";
    }
}

/** 
 * Verifica se um diretório contém arquivos
 *
 * @param array $files Lista de arquivos e pastas no diretório
 * @param string $dir Caminho do diretório
 * @return bool True se o diretório contiver arquivos, False caso contrário
 */
function hasFilesInDirectory($files, $dir)
{
    foreach ($files as $file) {
        if ($file !== "." && $file !== ".." && is_file($dir . DIRECTORY_SEPARATOR . $file)) {
            return true;
        }
    }
    return false;
}

/** 
 * Cria um arquivo ZIP com todos os arquivos de uma pasta e faz o download
 *
 * @param string $folderPath Caminho da pasta a ser compactada
 */
function downloadAllFilesInFolder($folderPath)
{
    $zip = new ZipArchive();
    $zipFileName = tempnam(sys_get_temp_dir(), 'zip');

    if ($zip->open($zipFileName, ZipArchive::CREATE) === TRUE) {
        $files = scandir($folderPath);
        foreach ($files as $file) {
            $filePath = $folderPath . DIRECTORY_SEPARATOR . $file;
            if (is_file($filePath)) {
                $zip->addFile($filePath, $file);
            }
        }
        $zip->close();

        serveFileDownload($zipFileName, 'arquivos.zip', 'application/zip', true);
    }
}

/** 
 * Faz o download de um arquivo individual
 *
 * @param string $filePath Caminho completo do arquivo
 */
function downloadFile($filePath)
{
    if (file_exists($filePath)) {
        serveFileDownload($filePath, basename($filePath), 'application/octet-stream');
    } else {
        echo "Erro: Arquivo não encontrado.<br>";
    }
}

/** 
 * Fornece um arquivo para download, com headers apropriados
 *
 * @param string $filePath Caminho completo do arquivo
 * @param string $fileName Nome do arquivo para download
 * @param string $contentType Tipo de conteúdo do arquivo
 * @param bool $deleteAfterDownload Se true, exclui o arquivo após o download
 */
function serveFileDownload($filePath, $fileName, $contentType, $deleteAfterDownload = false)
{
    header('Content-Type: ' . $contentType);
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Content-Length: ' . filesize($filePath));

    // Força o buffer a ser limpo antes de ler o arquivo
    ob_clean();
    flush();
    readfile($filePath);

    // Exclui o arquivo temporário somente após a leitura completa
    if ($deleteAfterDownload) {
        unlink($filePath);
    }
    exit;
}

// Manipulação das solicitações de download
if (isset($_GET['download_all'])) {
    downloadAllFilesInFolder(urldecode($_GET['download_all']));
} elseif (isset($_GET['download_file'])) {
    downloadFile(urldecode($_GET['download_file']));
} else {
    // Exibe a estrutura de diretórios com links de download
    listDirectoryTree($directory);
}
