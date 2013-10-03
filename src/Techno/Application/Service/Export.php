<?php

namespace Techno\Application\Service;

use PHPExcel_Writer_Excel2007;
use PHPExcel_Writer_Excel5;
use Techno\Domain\Category;
use Techno\Domain\Family\Family;
use Techno\Domain\Family\Cell;
use Techno\Domain\Family\Dimension;
use Xport\Spreadsheet\Builder\SpreadsheetModelBuilder;
use Xport\Spreadsheet\Exporter\PHPExcelExporter;
use Xport\MappingReader\YamlMappingReader;

/**
 * @author valentin.claras
 */
class Techno_Service_Export
{
    /**
     * Exporte la version de techno.
     *
     * @param string $format
     */
    public function stream($format)
    {
        $modelBuilder = new SpreadsheetModelBuilder();
        $export = new PHPExcelExporter();

        // Feuilles des Category.
        $modelBuilder->bind('categories', Category::loadRootCategories());
        $modelBuilder->bind('cellDigitalValue', __('UI', 'name', 'value'));
        $modelBuilder->bind('cellRelativeUncertainty', '+/- (%)');
        $modelBuilder->bindFunction('getAllFamilies', 'getAllFamilies');
        $modelBuilder->bindFunction(
            'getFamilyLabel',
            function (Family $family) {
                $label = '';

                $category = $family->getCategory();
                while ($category->getParentCategory() !== null) {
                    $label .= $category->getLabel() . ' / ';
                    $category = $category->getParentCategory();
                }
                $label .= $family->getLabel();

                $label .= ' (' . $family->getUnit()->getSymbol() . ')';

                return $label;
            }
        );
        $modelBuilder->bindFunction(
            'displayCellMemberForDimension',
            function (Cell $cell, Dimension $dimension) {
                foreach ($cell->getMembers() as $member) {
                    if ($dimension->hasMember($member)) {
                        return $member->getLabel();
                    }
                }
                return '';
            }
        );

        switch ($format) {
            case 'xls':
                $writer = new PHPExcel_Writer_Excel5();
                break;
            case 'xlsx':
            default:
                $writer = new PHPExcel_Writer_Excel2007();
                break;
        }

        $export->export(
            $modelBuilder->build(new YamlMappingReader(__DIR__ . '/export.yml')),
            'php://output',
            $writer
        );
    }
}
