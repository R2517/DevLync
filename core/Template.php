<?php
declare(strict_types=1);

/**
 * Simple PHP Template Engine
 * Renders PHP template files with extracted data variables.
 * Supports layouts by wrapping content in a main layout file.
 */
class Template
{
    private string $layoutPath = '';

    /**
     * Renders a template file with provided data, wrapped in a layout.
     *
     * @param string $template Relative path to template, e.g. 'home/index'
     * @param array  $data     Variables to extract into the template scope
     * @param string $layout   Layout file to use, e.g. 'layouts/main'
     * @return void
     */
    public function render(string $template, array $data = [], string $layout = 'layouts/main'): void
    {
        $content = $this->renderPartial($template, $data);

        if ($layout) {
            $layoutFile = VIEWS_PATH . '/' . $layout . '.php';
            if (file_exists($layoutFile)) {
                extract($data, EXTR_SKIP);
                include $layoutFile;
            } else {
                echo $content;
            }
        } else {
            echo $content;
        }
    }

    /**
     * Renders a template file and returns HTML as a string (no layout).
     *
     * @param string $template
     * @param array  $data
     * @return string
     */
    public function renderPartial(string $template, array $data = []): string
    {
        $file = VIEWS_PATH . '/' . $template . '.php';
        if (!file_exists($file)) {
            return '<!-- Template not found: ' . htmlspecialchars($template) . ' -->';
        }

        ob_start();
        extract($data, EXTR_SKIP);
        include $file;
        return (string) ob_get_clean();
    }

    /**
     * Renders a component (small reusable partial) and returns HTML.
     *
     * @param string $name Component name, e.g. 'breadcrumb'
     * @param array  $data Data to pass to the component
     * @return string
     */
    public function renderComponent(string $name, array $data = []): string
    {
        return $this->renderPartial('articles/components/' . $name, $data);
    }

    /**
     * Renders a component and directly echoes it.
     *
     * @param string $name
     * @param array  $data
     * @return void
     */
    public function component(string $name, array $data = []): void
    {
        echo $this->renderComponent($name, $data);
    }
}

/**
 * Global helper function to render a template component.
 *
 * @param string $name
 * @param array  $data
 * @return void
 */
function component(string $name, array $data = []): void
{
    global $tpl;
    echo $tpl->renderComponent($name, $data);
}
