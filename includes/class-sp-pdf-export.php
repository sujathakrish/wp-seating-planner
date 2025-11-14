<?php
namespace WPSeatingPlanner;

if (!defined('ABSPATH')) exit;

/**
 * Simple PDF Export Helper
 * Converts HTML to a basic printable PDF for seating plans.
 */
class SP_PDF_Export {

  public function render_html_to_pdf($html, $filename = 'export.pdf') {
    // Try FPDF if available
    if (!class_exists('FPDF')) {
      // If FPDF not present, attempt to load if bundled by WP or system
      $fpdfPath = ABSPATH . 'wp-includes/fpdf.php';
      if (file_exists($fpdfPath)) {
        require_once $fpdfPath;
      }
    }

    if (class_exists('FPDF')) {
      $this->generate_fpdf($html, $filename);
    } else {
      // Fallback: render plain HTML in browser printable mode
      header('Content-Type: application/pdf');
      header('Content-Disposition: inline; filename="' . $filename . '"');
      echo '<html><body><h2>Printable Seating Plan</h2>' . $html . '</body></html>';
      exit;
    }
  }

  /**
   * Generate PDF with FPDF (very lightweight)
   */
  private function generate_fpdf($html, $filename) {
    require_once(ABSPATH . 'wp-admin/includes/class-pclzip.php');
    // Load FPDF from plugin vendor if present
    if (file_exists(WP_PLUGIN_DIR . '/wp-seating-planner/vendor/fpdf/fpdf.php')) {
      require_once WP_PLUGIN_DIR . '/wp-seating-planner/vendor/fpdf/fpdf.php';
    }

    if (!class_exists('FPDF')) {
      // Cannot continue, fallback
      header('Content-Type: text/html');
      echo '<h2>PDF export unavailable. FPDF library missing.</h2>';
      echo $html;
      exit;
    }

    $pdf = new \FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', '', 10);

    // Convert basic HTML table tags into lines (lightweight conversion)
    $lines = preg_replace('/<[^>]+>/', '', $html);
    $lines = explode("\n", strip_tags($html));

    foreach ($lines as $line) {
      $pdf->MultiCell(0, 6, trim($line));
    }

    $pdf->Output('D', $filename);
    exit;
  }
}
