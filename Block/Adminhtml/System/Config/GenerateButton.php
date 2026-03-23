<?php declare(strict_types=1);

namespace MageOS\LlmTxt\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Button;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class GenerateButton extends Field
{
    protected $_template = 'MageOS_LlmTxt::system/config/generate_button.phtml';

    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function render(AbstractElement $element): string
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        return parent::render($element);
    }

    protected function _getElementHtml(AbstractElement $element): string
    {
        return $this->_toHtml();
    }

    public function getButtonHtml(): string
    {
        $button = $this->getLayout()
            ->createBlock(Button::class)
            ->setData([
                'id' => 'llmtxt_generate_button',
                'label' => __('Generate with AI'),
                'class' => 'action-default scalable',
            ]);

        return $button->toHtml();
    }

    public function getAjaxUrl(): string
    {
        return $this->getUrl('llmtxt/generate/index', ['store' => $this->getRequest()->getParam('store')]);
    }
}
