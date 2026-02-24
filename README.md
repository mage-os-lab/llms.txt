# LLM.txt for Magento 2 / Mage-OS

AI-powered LLM.txt generation module for Magento 2 and Mage-OS stores. Automatically creates optimized `llms.txt` files using OpenAI to help AI systems understand your store content.

## What is LLM.txt?

LLM.txt is a proposed standard (similar to `robots.txt`) that helps AI systems like ChatGPT, Claude, and others better understand your website by providing a concise, structured summary of your most important content.

Learn more: [llmstxt.org](https://llmstxt.org)

## Features

- 🤖 **AI-Powered Generation** - Uses OpenAI to intelligently curate your store content
- 📝 **Smart Content Selection** - Automatically analyzes categories, products, and pages
- ⚡ **One-Click Generation** - Click a button, review, and publish
- ✏️ **Fully Editable** - Edit AI-generated content or write your own
- 🏪 **Multi-Store Support** - Different content per store view
- 🚀 **Performance Optimized** - Full Page Cache integration
- 📊 **Token Counter** - Ensures content stays under recommended limits
- 🎯 **Standards Compliant** - Follows llmstxt.org specification

## Installation

```bash
composer require mage-os/module-llm-txt
bin/magento module:enable MageOS_LlmTxt
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

## Configuration

1. Navigate to: **Stores → Configuration → AI → LLM.txt**

2. **General Settings**
   - Enable the module
   - Set site name (optional, defaults to store name)
   - Add site description

3. **AI-Powered Generation**
   - Enter your OpenAI API key ([get one here](https://platform.openai.com/api-keys))
   - Select AI model (GPT-4o Mini recommended for speed and cost)
   - Click **"Generate with AI"**

4. **Review & Edit**
   - AI generates curated content from your store data
   - Review the generated markdown
   - Edit as needed
   - Save configuration

5. **Verify**
   - Visit `https://yourdomain.com/llms.txt`
   - Content is served as plain text

## How It Works

When you click "Generate with AI":

1. **Data Collection** - Gathers top categories, sample products, and key CMS pages
2. **AI Analysis** - Sends data to OpenAI with optimized prompt
3. **Content Generation** - AI creates concise, well-structured llms.txt
4. **Review** - You can edit the generated content before publishing

## Example Output

```markdown
# Your Store Name

> Your one-stop shop for quality products and exceptional service

## Shop by Category
- [Electronics](https://example.com/electronics.html): Explore cutting-edge tech and gadgets
- [Clothing](https://example.com/clothing.html): Fashion for every style and occasion

## Featured Products
- [Premium Headphones](https://example.com/headphones.html): Studio-quality sound
- [Organic Cotton T-Shirt](https://example.com/t-shirt.html): Sustainable fashion

## Customer Resources
- [About Us](https://example.com/about): Our story and mission
- [Contact](https://example.com/contact): Get in touch with our team
```

## Manual Override

Don't want to use AI? No problem:

1. Enable **"Use Manual Content"** checkbox
2. Write your own llms.txt in markdown format
3. Save

## Requirements

- PHP 8.1, 8.2, or 8.3
- Magento 2.4.x / Mage-OS 1.x
- OpenAI API key (for AI generation feature)
- Guzzle HTTP client (included with Magento)

## Cost

OpenAI API usage is minimal:
- ~$0.001 per generation with GPT-4o Mini
- ~$0.005 per generation with GPT-4o

You only pay when clicking "Generate with AI".

## Technical Details

### Architecture

- **Custom Router** - Matches `/llms.txt` path (follows `Magento_Robots` pattern)
- **Page Layout System** - Uses virtualType for plain text output
- **Block Rendering** - FPC integration via cache identities
- **AI Integration** - OpenAI API client with error handling
- **Store-Scoped Config** - All settings respect store scope

### Caching

- Content cached in Full Page Cache
- Cache tags: `llmtxt_{store_id}`
- Configurable TTL (default: 24 hours)
- Invalidate via admin or CLI

## Testing

Unit tests included:

```bash
vendor/bin/phpunit app/code/MageOS/LlmTxt/Test/Unit
```

Coverage:
- Config model
- Generator model
- StoreDataCollector
- OpenAI Client
- Router
- Block rendering

## License

MIT License - see [LICENSE](LICENSE) file for details

## Contributing

Contributions welcome! Please:
1. Fork the repository
2. Create a feature branch
3. Make your changes with tests
4. Submit a pull request

## Support

- **Issues**: [GitHub Issues](https://github.com/mage-os-lab/llms.txt/issues)
- **Discussions**: [GitHub Discussions](https://github.com/mage-os-lab/llms.txt/discussions)

## Related Resources

- [LLM.txt Official Site](https://llmstxt.org)
- [LLM.txt Directory](https://llmtxt.app)
- [OpenAI Platform](https://platform.openai.com)
- [Mage-OS](https://mage-os.org)
