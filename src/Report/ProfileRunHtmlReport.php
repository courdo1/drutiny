<?php

namespace Drutiny\Report;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Drutiny\Registry;

/**
 *
 */
class ProfileRunHtmlReport extends ProfileRunJsonReport {

  /**
   * @inheritdoc
   */
  public function render(InputInterface $input, OutputInterface $output) {
    // Check YAML supports markdown and needs to be converted into HTML before
    // we pass it into our report template.
    $parsedown = new \Parsedown();

    $render_vars = $this->getRenderVariables();

    // Render any markdown into HTML for the report.
    foreach ($render_vars['results'] as &$result) {
      $result['description'] = $parsedown->text($result['description']);
      $result['remediation'] = $parsedown->text($result['remediation']);
      $result['success'] = $parsedown->text($result['success']);
      $result['failure'] = $parsedown->text($result['failure']);
      $result['warning'] = $parsedown->text($result['warning']);
      $result['id'] = preg_replace('/[^0-9a-zA-Z]/', '', $result['title']);

      $result['state_class'] = 'info';
      if (!$result['is_notice'] && $result['status']) {
        $result['state_class'] = 'success';
      }
      elseif ($result['is_not_applicable']) {
        $result['state_class'] = 'default';
      }
      elseif (!$result['status']) {
        $result['state_class'] = 'danger';
      }
      if ($result['has_warning']) {
        $result['state_class'] = 'warning';
      }

    }

    usort($render_vars['results'], function ($a, $b) {
      if ($a['status'] != $b['status']) {
        return $a['status'] ? 1 : -1;
      }

      if ($a['has_warning'] && !$b['has_warning']) {
        return 1;
      }
      elseif (!$a['has_warning'] && $b['has_warning']) {
        return -1;
      }
      
      $order = [$a['title'], $b['title']];
      sort($order);
      return $a['title'] == $order[0] ? -1 : 1;
    });

    // Render the site report.
    $content = $this->renderTemplate('site', $render_vars);

    // Render the header/footer etc.
    $render_vars['content'] = $content;
    $content = $this->renderTemplate($this->info->get('template'), $render_vars);

    // Hack to fix table styles in bootstrap theme.
    $content = strtr($content, [
      '<table>' => '<table class="table table-hover">'
    ]);

    $filename = $input->getOption('report-filename');
    if ($filename == 'stdout') {
      echo $content;
      return;
    }
    if (file_put_contents($filename, $content)) {
      $output->writeln('<info>Report written to ' . $filename . '</info>');
    }
    else {
      echo $content;
      $output->writeln('<error>Could not write to ' . $filename . '. Output to stdout instead.</error>');
    }
  }

  /**
   * Render an HTML template.
   *
   * @param string $tpl
   *   The name of the .html.tpl template file to load for rendering.
   * @param array $render_vars
   *   An array of variables to be used within the template by the rendering engine.
   *
   * @return string
   */
  public function renderTemplate($tpl, array $render_vars) {
    $registry = new Registry();
    $loader = new \Twig_Loader_Filesystem($registry->templateDirs());
    $twig = new \Twig_Environment($loader, array(
      'cache' => sys_get_temp_dir() . '/drutiny/cache',
      'auto_reload' => TRUE,
    ));
    // $filter = new \Twig_SimpleFilter('filterXssAdmin', [$this, 'filterXssAdmin'], [
    //   'is_safe' => ['html'],
    // ]);
    // $twig->addFilter($filter);
    $template = $twig->load($tpl . '.html.twig');
    $contents = $template->render($render_vars);
    return $contents;
  }

}
