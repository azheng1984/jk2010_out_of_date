<?php
namespace Hyperframework\Web;

use Hyperframework\Common\ErrorException;
use Hyperframework\Common\Error;
use Hyperframework\Common\StackTraceFormatter;
use Hyperframework\Common\Config;
use Hyperframework\Common\ConfigException;
use Hyperframework\Common\TmpFileFullPathBuilder;
use Hyperframework\Common\DirectoryMaker;
use RuntimeException;

class Debugger {
    private $error;
    private $trace;
    private $outputBuffer;
    private $outputBufferLength;
    private $rootPath;
    private $rootPathLength;
    private $shouldHideExternal;
    private $shouldHideTrace;
    private $firstInternalStackFrameIndex;

    /**
     * @param object $error
     * @param string $outputBuffer
     */
    public function execute($error, $outputBuffer = null) {
        $this->error = $error;
        $this->outputBuffer = $outputBuffer;
        $this->outputBufferLength = strlen($outputBuffer);
        $rootPath = Config::getAppRootPath();
        $realRootPath = realpath($rootPath);
        if ($realRootPath !== false) {
            $rootPath = $realRootPath;
        } else {
            throw new ConfigException(
                "App root path '$rootPath' does not exist.'"
            );
        }
        $this->rootPath = $rootPath . DIRECTORY_SEPARATOR;
        $this->rootPathLength = strlen($this->rootPath);
        $this->shouldHideTrace = false;
        $this->shouldHideExternal = false;
        $this->trace = null;
        if ($this->error instanceof Error === false) {
            if ($this->error instanceof ErrorException) {
                $this->trace = $error->getSourceTrace();
            } else {
                $this->trace = $error->getTrace();
            }
            if ($this->isExternalFile($error->getFile())) {
                $this->firstInternalStackFrameIndex = null;
                foreach ($this->trace as $index => $frame) {
                    if (isset($frame['file'])
                        && $this->isExternalFile($frame['file']) === false
                    ) {
                        $this->firstInternalStackFrameIndex = $index;
                        break;
                    }
                }
                if ($this->firstInternalStackFrameIndex !== null) {
                    $this->shouldHideExternal = true;
                    $maxIndex = count($this->trace) - 1;
                    if ($maxIndex === $this->firstInternalStackFrameIndex) {
                        $this->shouldHideTrace = true;
                    }
                }
            }
        }
        if (headers_sent() === false) {
            header('Content-Type: text/html;charset=utf-8');
        }
        if ($this->error instanceof Error) {
            $type = htmlspecialchars(
                ucwords($error->getSeverityAsString()),
                ENT_NOQUOTES | ENT_HTML401 | ENT_SUBSTITUTE
            );
        } else {
            $type = get_class($error);
        }
        $message = (string)$error->getMessage();
        $title = $type;
        if ($message !== '') {
            $message = htmlspecialchars(
                $message, ENT_NOQUOTES | ENT_HTML401 | ENT_SUBSTITUTE
            );
            $title .= ': ' . $message;
        }
        $isSnapshotEnabled = Config::getBool(
            'hyperframework.web.debugger.enable_snapshot', false
        );
        if ($isSnapshotEnabled) {
            ob_start();
        }
        echo '<!DOCTYPE html><html><head><meta http-equiv="Content-Type"',
            ' content="text/html;charset=utf-8"/><title>', $title, '</title>';
        $this->renderCss();
        echo '</head><body class="no-touch"><table id="page-container"><tbody>';
        $this->renderNav($type, $message);
        $this->renderContent($type, $message);
        $this->renderJavascript();
        echo '</tbody></table></body></html>';
        if ($isSnapshotEnabled) {
            list($usec, $sec) = explode(' ', microtime());
            $usec = substr($usec, 2);
            $directory = TmpFileFullPathBuilder::build(
                'debug' . DIRECTORY_SEPARATOR . date('Ymd', $sec)
            );
            DirectoryMaker::make($directory);
            $path = $directory . DIRECTORY_SEPARATOR . date('His_', $sec)
                . $usec . '.html';
            $content = ob_get_contents();
            ob_end_clean();
            file_put_contents($path, $content);
            $path = 'file://' . str_replace(DIRECTORY_SEPARATOR, '/', $path);
            echo str_replace(
                '</body>',
                '<div id="snapshot"><span>Snapshot</span>'
                    . ' <a class="path" href="' . $path . '">'
                    . $path . '</a></div></body>',
                $content
            );
        }
    }

    /**
     * @param string $path
     * @return boolean
     */
    private function isExternalFile($path) {
        $relativePath = $this->getRelativePath($path);
        if ($relativePath === $path) {
            return true;
        }
        if (strncmp($relativePath, 'vendor' . DIRECTORY_SEPARATOR, 7) === 0) {
            return true;
        }
        return false;
    }

    /**
     * @param string $type
     * @param string $message
     */
    private function renderContent($type, $message) {
        echo '<tr><td id="content"><table id="error"><tbody>';
        $this->renderErrorHeader($type, $message);
        echo '<tr><td id="file-wrapper">';
        $this->renderFile();
        echo '</td></tr>';
        if ($this->error instanceof Error === false) {
            echo '<tr><td id="stack-trace-wrapper"';
            if ($this->shouldHideTrace) {
                echo ' class="hidden"';
            }
            echo '>';
            $this->renderStackTrace();
            echo '</td></tr>';
        }
        echo '</tbody></table></td></tr>';
    }

    /**
     * @param string $type
     * @param string $message
     */
    private function renderErrorHeader($type, $message) {
        if ($this->error instanceof Error === false) {
            $type = str_replace('\\', '<span>\</span>', $type);
        }
        echo '<tr><td id="error-header"><h1>', $type, '</h1>';
        $message = trim($message);
        if ($message !== '') {
            echo '<div id="message">', $message, '</div>';
        }
        echo '</td></tr>';
    }

    private function renderFile() {
        echo '<div id="file">';
        if ($this->shouldHideExternal) {
            $frame = $this->trace[$this->firstInternalStackFrameIndex];
            $path = $frame['file'];
            $errorLineNumber = $frame['line'];
            echo '<table id="file-switch"><tbody><tr><td>',
                '<h2>File</h2></td><td><table><tbody><tr><td id="internal">',
                '<span>Internal</span></td><td id="external"><a>',
                'External</a></td></tr></tbody></table></td></tr></tbody>',
                '</table><div id="internal-file">';
            $this->renderFileContent($path, $errorLineNumber);
            echo '</div><div id="external-file" class="hidden">';
        } else {
            echo '<h2>File</h2>';
        }
        $path = $this->error->getFile();
        $errorLineNumber = $this->error->getLine();
        $this->renderFileContent($path, $errorLineNumber);
        if ($this->shouldHideExternal) {
            echo '</div>';
        }
        echo '</div>';
    }

    /**
     * @param string $path
     * @param int $errorLineNumber
     */
    private function renderFileContent($path, $errorLineNumber) {
        $this->renderPath(
            $path, ' <span class="line">' . $errorLineNumber . '</span>'
        );
        echo '<div class="file-content"><table><tbody><tr>',
            '<td class="index"><div class="index-content">';
        $lines = $this->getLines($path, $errorLineNumber);
        foreach ($lines as $number => $line) {
            if ($number === $errorLineNumber) {
                echo '<div class="error-line-number"><div>',
                    $number, '</div></div>';
            } else {
                echo '<div class="line-number"><div>', $number, '</div></div>';
            }
        }
        echo '</div></td><td><pre class="content"><div>';
        foreach ($lines as $number => $line) {
            if ($number === $errorLineNumber) {
                echo '<span class="error-line">', $line , "\n</span>";
            } else {
                echo $line , "\n";
            }
        }
        echo '</div></pre></td></tr></tbody></table></div>';
    }

    private function renderStackTrace() {
        echo '<table id="stack-trace">',
            '<tr><td class="content"><h2>Stack Trace</h2><table><tbody>';
        $index = 0;
        $last = count($this->trace) - 1;
        foreach ($this->trace as $frame) {
            if ($frame !== '{main}') {
                $invocation = StackTraceFormatter::formatInvocation($frame);
                echo '<tr id="frame-', $index, '"';
                if ($this->shouldHideExternal
                    && $this->shouldHideTrace === false
                ) {
                    if ($index < $this->firstInternalStackFrameIndex) {
                        echo ' class="hidden"';
                    }
                    echo '><td class="index">',
                        $index - $this->firstInternalStackFrameIndex;
                } else {
                    echo '><td class="index">', $index;
                }
                echo '</td><td class="value';
                if ($index === $last) {
                    echo ' last';
                }
                echo '"><div class="frame"><div class="position">';
                if (isset($frame['file'])) {
                    $this->renderPath(
                        $frame['file'],
                        ' <span class="line">' . $frame['line'] . '</span>'
                    );
                } else {
                    echo '<span class="internal">internal function</span>';
                }
                echo '</div><div class="invocation"><code>', $invocation,
                    '</code></div></div></td></tr>';
            }
            ++$index;
        }
        if ($index === 0) {
            echo '<tr><td class="empty">empty</td></tr>';
        }
        echo '</tbody></table></td></tr></table>';
    }

    /**
     * @param string $path
     * @param int $errorLineNumber
     * @return array
     */
    private function getLines($path, $errorLineNumber) {
        $file = file_get_contents($path);
        $tokens = token_get_all($file);
        $firstLineNumber = 1;
        if ($errorLineNumber > 6) {
            $firstLineNumber = $errorLineNumber - 5;
        }
        $previousLineIndex = null;
        if ($firstLineNumber > 0) {
            foreach ($tokens as $index => $value) {
                if (is_string($value) === false) {
                    if ($value[2] < $firstLineNumber) {
                        $previousLineIndex = $index;
                    } else {
                        break;
                    }
                }
            }
        }
        $lineNumber = 0;
        $result = [];
        $buffer = '';
        foreach ($tokens as $index => $value) {
            if ($previousLineIndex !== null && $index < $previousLineIndex) {
                continue;
            }
            if (is_string($value)) {
                if ($value === '"') {
                    $buffer .= '<span class="string">' . $value . '</span>';
                } else {
                    $buffer .= '<span class="keyword">' . htmlspecialchars(
                        $value, ENT_NOQUOTES | ENT_HTML401 | ENT_SUBSTITUTE
                    ) . '</span>';
                }
                continue;
            }
            $lineNumber = $value[2];
            $type = $value[0];
            $content = str_replace(["\r\n", "\r"], "\n", $value[1]);
            $lines = explode("\n", $content);
            $lastLine = array_pop($lines);
            foreach ($lines as $line) {
                if ($lineNumber >= $firstLineNumber) {
                    $result[$lineNumber] =
                        $buffer . $this->formatToken($type, $line);
                    $buffer = '';
                }
                ++$lineNumber;
            }
            $buffer .= $this->formatToken($type, $lastLine);
            if ($lineNumber > $errorLineNumber + 5) {
                $buffer = false;
                break;
            }
        }
        if ($buffer !== false) {
            $result[$lineNumber] = $buffer;
        }
        if (isset($result[$errorLineNumber + 6])) {
            return array_slice(
                $result, 0, $errorLineNumber - $firstLineNumber + 6, true
            );
        }
        return $result;
    }

    /**
     * @param int $type
     * @param string $content
     * @return string
     */
    private function formatToken($type, $content) {
        $class = null;
        switch ($type) {
            case T_ENCAPSED_AND_WHITESPACE:
            case T_CONSTANT_ENCAPSED_STRING:
                $class = 'string';
                break;
            case T_WHITESPACE:
            case T_STRING:
            case T_NUM_STRING:
            case T_VARIABLE:
            case T_DNUMBER:
            case T_LNUMBER:
            case T_HALT_COMPILER:
            case T_EVAL:
            case T_CURLY_OPEN:
            case T_UNSET:
            case T_STRING_VARNAME:
            case T_PRINT:
            case T_REQUIRE:
            case T_REQUIRE_ONCE:
            case T_INCLUDE:
            case T_INCLUDE_ONCE:
            case T_ISSET:
            case T_LIST:
            case T_CLOSE_TAG:
            case T_OPEN_TAG:
            case T_OPEN_TAG_WITH_ECHO:
                break;
            case T_COMMENT:
            case T_DOC_COMMENT:
                $class = 'comment';
                break;
            case T_INLINE_HTML:
                $class = 'html';
                break;
            default:
                $class = 'keyword';
        }
        $content = htmlspecialchars(
            $content, ENT_NOQUOTES | ENT_HTML401 | ENT_SUBSTITUTE
        );
        if ($class === null) {
            return $content; 
        }
        return '<span class="' . $class . '">' . $content . '</span>';
    }

    /**
     * @param string $path
     * @param string $suffix
     */
    private function renderPath($path, $suffix = '') {
        echo '<div class="path"><code>', $path, '</code>', $suffix, '</div>';
    }

    /**
     * @param string $path
     * @return string
     */
    private function getRelativePath($path) {
        if (strncmp($this->rootPath, $path, $this->rootPathLength) === 0) {
            $path = substr($path, $this->rootPathLength);
        }
        return $path;
    }

    /**
     * @param string $type
     * @param string $message
     */
    private function renderNav($type, $message) {
        if ($this->error instanceof Error === false) {
            $type = str_replace('\\', '<span>\</span>', $type);
        }
        echo '<tr><td id="nav-wrapper">',
            '<div id="nav"><div class="wrapper">',
            '<div class="selected" id="nav-error"><div>Error</div></div>',
            '<div id="nav-output"><a>Output</a></div></div></div></td></tr>';
    }

    /**
     * @param bool $shouldReturnText
     * @return int|string
     */
    private function getMaxOutputBufferSize($shouldReturnText = false) {
        $size = strtolower(trim(Config::get(
            'hyperframework.web.debugger.max_output_buffer_size'
        )));
        if ($size === '') {
            return -1;
        }
        if ((int)$size <= 0) {
            return 0;
        }
        if (strlen($size) < 2) {
            return (int)$size;
        }
        $type = substr($size, -1);
        $size = (int)$size;
        switch ($type) {
            case 'g':
                if ($shouldReturnText) {
                    return $size . 'GB';
                }
                $size *= 1024;
            case 'm':
                if ($shouldReturnText) {
                    return $size . 'MB';
                }
                $size *= 1024;
            case 'k':
                if ($shouldReturnText) {
                    return $size . 'KB';
                }
                $size *= 1024;
        }
        return $size;
    }

    private function renderJavascript() {
        $isOverflow = false;
        $maxSize = $this->getMaxOutputBufferSize();
        if ($maxSize >= 0 && $this->outputBufferLength >= $maxSize) {
            $isOverflow = true;
            $outputBuffer = mb_strcut($this->outputBuffer, 0, $maxSize);
        } else {
            $outputBuffer = $this->outputBuffer;
        }
        $outputBuffer = str_replace(["\r\n", "\r"], "\n", $outputBuffer);
        $outputBuffer = json_encode(htmlspecialchars(
            $outputBuffer, ENT_NOQUOTES | ENT_HTML401 | ENT_SUBSTITUTE
        ), JSON_UNESCAPED_UNICODE);
        $shouldHideTrace = 'null';
        $firstInternalStackFrameIndex = 'null';
        if ($this->shouldHideExternal) {
            if ($this->shouldHideTrace === true) {
                $shouldHideTrace = 'true';
                $firstInternalStackFrameIndex = 'null';
            } else {
                $shouldHideTrace = 'false';
                $firstInternalStackFrameIndex =
                    $this->firstInternalStackFrameIndex;
            }
        }
        if ($this->trace !== null) {
            $stackFrameCount = count($this->trace);
        } else {
            $stackFrameCount = 0;
        }
?>
<script type="text/javascript">
document.body.ontouchstart = function() {
    document.body.className = '';
    isHandheld = true;
};
var isHandheld = false;
var errorContent = null;
var outputContent = null;
var shouldHideTrace = <?= $shouldHideTrace ?>;
var stackFrameCount = <?= $stackFrameCount ?>;
var firstInternalStackFrameIndex = <?= $firstInternalStackFrameIndex ?>;
var outputBuffer = <?= $outputBuffer ?>;
var outputSizeHtml = '<div id="size">Size: <span><?php
    if ($this->outputBufferLength === 1) {
        echo '1 byte';
    } else {
        $size = $this->outputBufferLength / 1024;
        $prefix = '';
        $suffix = '';
        if ($size > 1) {
            $prefix = ' (';
            $suffix = ')';
            $tmp = $size / 1024; 
            if ($tmp > 1) {
                $size = $tmp;
                $tmp /= 1024;
                if ($tmp > 1) {
                    echo sprintf("%.1f", $tmp), ' GB';
                } else {
                    echo sprintf("%.1f", $size), ' MB';
                }
            } else {
                echo sprintf("%.1f", $size), ' KB';
            }
        }
        echo $prefix, $this->outputBufferLength, ' bytes', $suffix;
    }
    echo '</span></div>';?>';
function showOutput() {
    if (errorContent != null) {
        return;
    }
    var errorTab = document.getElementById("nav-error");
    errorTab.innerHTML = '<a href="javascript:showError()">Error</a>';
    errorTab.className = '';
    var outputTab = document.getElementById("nav-output");
    outputTab.innerHTML = '<div>Output</div>';
    outputTab.className = 'selected';
    var contentDiv = document.getElementById("content");
    if (outputContent != null) {
        errorContent = contentDiv.innerHTML;
        contentDiv.innerHTML = outputContent;
        outputContent = null;
        return;
    }
    var isOverflow = <?= json_encode($isOverflow) ?>;
    var html = '';
    if (isOverflow) {
    	html += '<tr><td class="notice"><span>Notice: </span>';
        var maxSize = <?= $this->getMaxOutputBufferSize() ?>;
        if (maxSize !== 0) {
            html += 'Output is partial. The size is larger than'
                + ' limitation ('
                + '<?= $this->getMaxOutputBufferSize(true) ?>' + ').';
        } else {
        	html += 'Output display is turn off.';
        }
        html += '</td></tr>';
    }
    var responseBodyHtml = outputSizeHtml;
    if (outputBuffer != '') {
        responseBodyHtml += '<div id="toolbar"><a href="'
            + 'javascript:showRawOutput()">Raw</a></div>';
    }
    responseBodyHtml += getOutputBufferHtml();
    errorContent = contentDiv.innerHTML;
    contentDiv.innerHTML = '<table id="output"><tbody>' + html
        + '<tr><td id="response-body" class="response-body">'
        + responseBodyHtml + '</td></tr></tbody></table>';
}

function showLineNumbers() {
    document.getElementById("response-body").innerHTML = outputSizeHtml
        + '<div id="toolbar">'
        + '<a href="javascript:showRawOutput()">Raw</a> </div>'
        + getOutputBufferHtml();
}

function showRawOutput() {
    var html = outputSizeHtml + '<div id="toolbar">'
        + '<a href="javascript:showLineNumbers()">Show Line Numbers</a>'
    if (isHandheld == false) {
        html  += ' &nbsp;<a href="javascript:selectAll()">Select All</a>'
    }
    html += "</div><div id=\"raw\"><pre><div>"
        + outputBuffer + "</div></pre></div>";
    document.getElementById("response-body").innerHTML = html;
}

function selectAll() {
    var text = document.getElementById('raw');
    if (window.getSelection) {
        var selection = window.getSelection();
        var range = document.createRange();
        range.selectNodeContents(text);
        selection.removeAllRanges();
        selection.addRange(range);
    } else if (document.body.createTextRange) {
        var range = document.body.createTextRange();
        range.moveToElementText(text);
        range.select();
    }
}

function getOutputBufferHtml() {
    var result = '';
    var lines = outputBuffer.split("\n");
    var count = lines.length;
    var last = count - 1;
    var isCssLineNumber = false;
    var contentTag = 'pre';
    //for copy content
    if (typeof CSS != 'undefined' && typeof CSS.supports != 'undefined') {
        if (CSS.supports('white-space', 'pre-wrap')) {
            //for firefox
            contentTag = 'error';
        }
    }
    if (typeof window.getComputedStyle != 'undefined') {
        if (typeof window.getComputedStyle(document.body, null).content
            != 'undefined'
        ) {
            isCssLineNumber = true;
        }
    }
    for (var index = 0; index < count; ++index) {
        result += '<tr><td class="';
        if (count == 1) {
            result += 'first last ';
        } else if (index == 0) {
            result += 'first ';
        } else if (index == last) {
            result += 'last ';
        }
        result += 'line-number"';
        if (isCssLineNumber) {
            result += ' data-line="' + (index + 1) + '"';
        }
        result += '>';
        if (isCssLineNumber == false) {
            result += (index + 1);
        }
        result += '</td><td';
        if (count == 1) {
            result += ' class="first last"';
        } else if (index == 0) {
            result += ' class="first"';
        } else if (index == last) {
            result += ' class="last"';
        }
        result += '><' + contentTag + '>' + lines[index] + '</'
            + contentTag + '></td></tr>';
    }
    return '<table><tbody>' + result + '</tbody></table>';
}

function showError() {
    if (errorContent == null) {
        return;
    }
    var errorTab = document.getElementById("nav-error");
    errorTab.innerHTML = '<div>Error</div>';
    errorTab.className = 'selected';
    var outputTab = document.getElementById("nav-output");
    outputTab.innerHTML = '<a href="javascript:showOutput()">Output</a>';
    outputTab.className = '';
    var contentDiv = document.getElementById("content");
    outputContent = contentDiv.innerHTML;
    contentDiv.innerHTML = errorContent;
    errorContent = null;
}

function showExternal() {
    document.getElementById("internal-file").className = "hidden";
    document.getElementById("external-file").className = "";
    if (shouldHideTrace) {
        document.getElementById('stack-trace-wrapper').className = '';
    } else {
        for (var index = 0; index < stackFrameCount; ++index) {
            var node = document.getElementById('frame-' + index);
            node.className = '';
            var child = node.firstChild;
            child.innerHTML =
                parseInt(child.innerHTML) + firstInternalStackFrameIndex;
        }
    }
    var button = document.getElementById("internal");
    button.innerHTML = '<a href="javascript:showInternal()">Internal</a>';
    button = document.getElementById("external");
    button.innerHTML = '<span class="selected">External</span>';
}

function showInternal() {
    document.getElementById("internal-file").className = "";
    document.getElementById("external-file").className = "hidden";
    if (shouldHideTrace) {
        document.getElementById('stack-trace-wrapper').className = 'hidden';
    } else {
        for (var index = 0; index < stackFrameCount; ++index) {
            var node = document.getElementById('frame-' + index);
            if (index < firstInternalStackFrameIndex) {
                node.className = 'hidden';
            }
            var child = node.firstChild;
            child.innerHTML =
                parseInt(child.innerHTML) - firstInternalStackFrameIndex;
        }
    }
    var button = document.getElementById("internal");
    button.innerHTML = '<span>Internal</span>';
    button = document.getElementById("external");
    button.innerHTML = '<a href="javascript:showExternal()">External</a>';
}

document.getElementById("nav-output").innerHTML =
    '<a href="javascript:showOutput()">Output</a>';
if (document.getElementById("external") !== null) {
    document.getElementById("external").firstChild.href =
        'javascript:showExternal()';
}
</script>
<?php
    }

    private function renderCss() {
?>
<style>
body {
    background: #fff;
    color: #333;
    font-family: Helvetica, Arial, sans-serif;
    font-size: 13px;
    /* Prevent font scaling in landscape while allowing user zoom */
    -moz-text-size-adjust: 100%;
    -ms-text-size-adjust: 100%;
    -webkit-text-size-adjust: 100%;
}
table {
    font-size: 13px;/* firebug preview */
    border-collapse: collapse;
}
td {
    padding: 0;
}
a {
    text-decoration: none;
    color: #333;
}
h1 span {
    color: #bbb;
    padding: 0 5px;
}
.no-touch a:hover {
    color: #09d;
}
pre, h1, h2, body {
    margin: 0;
}
h2 {
    font-size: 18px;
    font-family: "Times New Roman", Times, serif;
    padding: 0 10px;
}
#message, code, pre {
    font-family: Consolas, "Liberation Mono", Monospace, Menlo, Courier;
}
#page-container {
    width: 100%;
    min-width: 200px;
    _width: expression(
        (document.documentElement.clientWidth || document.body.clientWidth)
            < 200 ? "200px" : ""
    );
}
#nav-wrapper {
    background: #f8f8f8;
}
#error-header {
    padding-bottom: 10px;
}
h1 {
    font-size: 21px;
    line-height: 25px;
    color: #e44;
    padding: 5px 10px 5px 10px;
}
h1, #message {
    font-weight: normal;
}
#message {
    font-size: 14px;
    padding: 2px 10px 5px 10px;
    line-height: 20px;
}
#error, #output {
    border: 1px solid #ccc;
    width: 100%;
    background: #fff;
}
#error {
	border: 0;
}
#nav {
    position: relative;
    height: 39px;
    border-bottom: 1px solid #ccc;
}
#nav a {
    color: #333;
    line-height: 28px;
    padding: 7px 25px 7px;
}
#nav a:hover {
    background-image: linear-gradient(#f8f8f8, #e5e5e5);
    color: #000;
}
#nav .wrapper {
    padding: 10px 0 0 10px;
    font-weight: bold;
    position: absolute;
}
#nav .wrapper div {
    overflow: hidden;
    float: left;
    line-height: 16px;
    background-image: linear-gradient(#fcfcfc, #eee);
    border: 1px solid #ccc;
    border-bottom: 0;
    border-radius: 2px 2px 0 0;
}
#nav .wrapper div.selected {
    background-image: none;
    border: 0;
    padding: 0;
    height: 32px;
}
#nav .wrapper .selected div {
    background-image: none;
    background-color: #fff;
}
#nav .selected div {
    border-bottom: 0;
    padding: 6px 25px 7px;
}
#nav-output {
    margin-left: 5px;
}
#content {
    padding: 10px;
}
#stack-trace-wrapper {
	border: 1px solid #ccc;
}
.path {
    word-break: break-all; /* ie */
    word-wrap: break-word;
}
#file-wrapper {
    padding: 10px;
    border: 1px solid #ccc;
	background-color: #f8f8f8;
}
#file h2 {
	padding-left: 0;
}
#file .path {
    padding: 5px 5px 8px 0;
}
#response-body a {
    background-image: linear-gradient(#fcfcfc, #eee);
    background-color: #f1f1f1;
    border: 1px solid #d5d5d5;
    border-radius: 3px;
    padding: 4px 10px;
    font-size: 12px;
    word-break: keep-all;
    white-space: nowrap;
}
.no-touch #response-body a:hover {
    background-image: linear-gradient(#f8f8f8, #e5e5e5);
    color: #000;
}
.hidden {
    display: none;
}
#file table {
    width: 100%;
    line-height: 18px;
}
#file pre {
    font-size: 13px;
    margin-right:10px;
    color: #00b;
}
#file .index .index-content {
    padding: 0;
    margin-left:10px;
}
#file .index {
    width:1px;
    text-align:right;
}
#file .index div {
    color:#aaa;padding:0 5px;
    font-size:12px;
}
#file .index .line-number {
    padding: 0 5px 0 0;
}
#file .line-number div {
    border-right:1px solid #e1e1e1;
}
#file .index .error-line-number {
    padding: 0 5px 0 0;
    background: #ffa;
}
#file .index .error-line-number div {
    background-color:#d11;
    color:#fff;
    border-right:1px solid #e1e1e1;
}
#file pre .keyword {
    color: #070;
}
#file pre .string {
    color: #d00;
}
#file pre .comment {
    color: #f80;
}
#file pre .html {
    color: #000;
}
#file .error-line {
    display: block;
    background: #ffa;
}
#stack-trace {
    width: 100%;
}
#stack-trace .content {
    padding: 10px;
}
#stack-trace h2 {
    padding: 0 0 10px 0;
}
#stack-trace table {
    width: 100%;
    border-radius: 2px;
    border-spacing: 0; /* ie6 */
}
#stack-trace .path {
    color: #333;
}
#stack-trace .internal {
    color: #333;
    font-weight: bold;
}
#file .path .line, #stack-trace .line {
    font-size: 12px;
    color: #333;
    border-left: 1px solid #d5d5d5;
    padding-left: 8px;
    word-break: keep-all;
    white-space: nowrap;
}
#file .path code, #stack-trace .path code {
    padding-right: 3px;
}
#stack-trace table .value {
}
#stack-trace table .last {
    border-bottom: 0;
}
#stack-trace .index {
    padding: 8px 5px 0 5px;
    width: 1px;
    color: #aaa;
    font-size:12px;
    border-right: 1px solid #e1e1e1;
    text-align: right;
    vertical-align: top;
}
#stack-trace .frame {
    background: #f8f8f8;
    padding: 7px 10px 10px;
    border-top: 1px solid #e1e1e1;
    border-right: 1px solid #e1e1e1;
}
#stack-trace .last .frame {
    border-bottom: 1px solid #e1e1e1;
}
#stack-trace .invocation {
    background: #fff;
    border-left: 2px solid #e44;
    padding: 5px 10px;
    color: #777;
    margin-top: 7px;
    box-shadow: 0 1px 2px rgba(0,0,0,.1);
}
#stack-trace .invocation code {
    word-wrap: break-word;
    word-break: break-all;
}
#stack-trace .empty {
    color: #999;
}
#response-body {
    padding: 10px;
    background:#f8f8f8;
}
#response-body table {
    line-height: 18px;
}

#output-button-top-wrapper {
    margin-bottom: 10px;
}
#output-button-bottom-wrapper {
    margin-top: 10px;
}
#response-body table {
    background-color: #fff;
    line-height:18px;
    width: 100%;
    border:1px solid #e1e1e1;
    border-radius: 2px;
}
#response-body td {
    padding: 0 5px;
}
#response-body td.first {
    padding-top: 5px;
}
#response-body td.last {
    padding-bottom: 5px;
}
#response-body .line-number {
    background-color: #f8f8f8;
    border-right:1px solid #e1e1e1;
    font-size: 11px;
    color: #999;
    text-align:right;
    vertical-align: top;
    padding: 0 5px;
    width: 1px;
}
#response-body .line-number:before {
    content: attr(data-line);
}
.notice {
    background: #ff9;
    padding: 10px;
}
.notice span {
    font-weight: bold;
}
#raw {
    width: 100%; 
    border: 1px solid #e1e1e1;
    background: #fff;
	clear: both;
}
#raw pre {
    padding: 5px;
}
#size {
    padding: 5px 0 15px 0;
    float:left;
    color: #999;
}
#size span {
    color: #333;    
}
#toolbar {
    padding-bottom: 10px;
    line-height: 24px;
    float:right;
}
#file #file-switch a {
    color: #909090;
}
#file #file-switch {
    width: 150px;
}
#file #file-switch a:hover {
    background-image: linear-gradient(#f8f8f8, #e5e5e5);
}
#internal a, #internal span, #external a, #external span {
	display: block;
    width: 58px;
	line-height: 22px;
    text-align: center;
    border:1px solid;
    background-image: linear-gradient(#fcfcfc, #eee);
    border-color: #ccc;
	font-size: 12px;
}
#internal a, #internal span {
	border-radius: 3px 0 0 3px;
    border-right: 0;
}
#internal span {
	border-right: 1px solid;
}
#external a, #external span  {
    border-radius: 0 3px 3px 0;
    border-left: 0;
}
#file-switch span {
    background-image: none;
    color: #fff;
    background-color: #909090;
    border-color: #909090;
}
#file-switch table {
	box-shadow: 1px 1px 2px rgba(0,0,0,.1);
    border-radius: 3px;
}
.file-content {
    padding: 10px 0;
    background: #fff;
    border: 1px solid #e1e1e1;
}
#internal-file .path, #external-file .path {
    padding-top: 8px;
}
#snapshot {
    line-height: 20px;
    text-align: center;
    font-size: 12px;
    padding-bottom: 10px;
    margin: 0 20px;
}
#snapshot a {
    color: #aaa;
    padding: 3px 0;
}
#snapshot a:hover {
    color: #777;
}
#snapshot span {
    background: #f8f8f8;
    color: #999;
    padding: 3px 6px;
    margin-right: 3px;
    border-radius: 2px;
    box-shadow: 0 1px 2px rgba(0,0,0,.1);
}
</style>
<?php
    }
}
