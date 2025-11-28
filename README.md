# Flux Media Optimizer by Flux Plugins

One-click AVIF/WebP image optimization and video compression for WordPress. Automatically convert images to modern formats and optimize videos for faster page loads.

**Source Code**: [https://github.com/stratease/flux-media-optimizer](https://github.com/stratease/flux-media-optimizer)

## 🚀 Features

### Image Optimization
- **Hybrid Approach**: Creates both WebP and AVIF formats for optimal performance
- **Smart Serving**: Uses `<picture>` tags or direct URL replacement based on settings
- **Quality Control**: Configurable quality settings with version-specific AVIF optimization
- **Automatic Processing**: Convert on upload and bulk process existing media
- **WordPress Integration**: Seamless integration with Gutenberg blocks and responsive images
- **GIF Support**: Full support for static and animated GIFs with animation preservation (requires Imagick)

### Video Optimization
- **FFmpeg-Powered**: Uses PHP-FFmpeg for efficient MP4/WebM generation
- **Size & Quality Controls**: Configure bitrate and presets to balance clarity and savings
- **Bulk & On-Upload Support**: Convert existing library items or new uploads automatically

### Global CDN & Cloud Processing (SaaS)
- **Global Content Delivery**: Optimized assets are stored on Flux's Google Cloud CDN, ensuring lightning-fast delivery worldwide regardless of visitor location
- **Offloaded Processing**: Heavy image/video conversion tasks are handled by our external service, reducing load on your server
- **Automatic Upload & Optimization**: New uploads are automatically sent to our processing service and returned as optimized assets
- **Secure Integration**: Uses license key authentication and secure webhooks for reliable communication
- **Remote Quota Management**: Usage is tracked automatically, with clear visibility in your WordPress admin

### Modern Admin Interface
- **React Router**: Hash-based routing with Link components
- **Material-UI**: Professional design system with Grid layout
- **React Query**: Efficient data fetching with caching
- **Auto-Save**: Real-time settings saving with visual feedback
- **Skeleton Loading**: Professional loading states
- **WordPress i18n**: Full internationalization support

## 🔒 Privacy & Data Protection

### Local Processing (Default)
- All image and video processing happens locally on your server
- No external data sharing without explicit consent
- Media files never leave your WordPress installation

### Optional SaaS Service
- Opt-in only with explicit user consent and license activation
- External processing via secure cloud infrastructure
- Optimized files are stored on Flux's Global Google Cloud CDN for performance
- Email communications for service updates and marketing
- Full compliance with WordPress.org guidelines

**Privacy Policy**: [https://fluxplugins.com/privacy-policy/](https://fluxplugins.com/privacy-policy/)

## 🛠️ Quick Start

### Installation
```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install

# Build frontend
npm run build
```

### Development
```bash
# Frontend development with hot reload
npm run dev

# Run tests
./vendor/bin/phpunit
npm test

# Lint code
npm run lint
composer run lint
```

## 🏗️ Architecture

This plugin uses a modern, decoupled architecture that separates business logic from WordPress dependencies:

- **Pure Business Logic**: `ImageConverter` and `VideoConverter` are WordPress-independent
- **Provider Pattern**: `WordPressProvider` handles all WordPress integration
- **Dependency Injection**: Uses interfaces for testable, decoupled components
- **Unified Converter Interface**: Fluent API with centralized format constants
- **External Optimization**: `ExternalOptimizationProvider` manages communication with the SaaS processing service and CDN

## 📁 Project Structure

```
flux-media-optimizer/
├── app/                          # Main application code
│   ├── Services/                 # Business logic services
│   ├── Http/Controllers/         # REST API controllers
│   ├── Interfaces/               # Contract definitions
│   └── Processors/               # Image/video processors
├── assets/js/src/                # React frontend
│   ├── components/               # React components
│   ├── hooks/                    # Custom React hooks
│   └── services/                 # API services
└── tests/                        # Test files
```

## 🚀 API Endpoints

All endpoints are prefixed with `/wp-json/flux-media-optimizer/v1/`:

- `GET /system/status` - System status and capabilities
- `GET /options` - Plugin options
- `POST /options` - Update plugin options
- `GET /conversions/stats` - Conversion statistics
- `POST /conversions/bulk` - Start bulk conversion
- `GET /logs` - Get logs with pagination
- `POST /webhook` - Callback endpoint for external processing service

## 🔮 Future Roadmap

### Planned Enhancements
- **AI-Powered Optimization**: Machine learning-based compression
- **Advanced Analytics**: Detailed conversion metrics
- **Extended Format Support**: Additional formats via SaaS API

### Technical Improvements
- **Performance**: Further optimization and caching
- **Scalability**: Support for high-volume sites
- **Monitoring**: Enhanced logging and monitoring
- **Testing**: Comprehensive test coverage

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guidelines](CONTRIBUTING.md) for detailed information on:

- Development setup and workflow
- Coding standards and architecture patterns
- Testing requirements
- Pull request process
- Security guidelines

## 📄 License

GPL-2.0+ - See [LICENSE](LICENSE) file for details.

## 🆘 Support

### Documentation
- [Contributing Guidelines](CONTRIBUTING.md) - Development and architecture details
- Code comments explain implementation details
- API documentation is embedded in endpoint methods

### Troubleshooting
- Check system requirements
- Verify PHP extensions (GD/Imagick, FFmpeg)
- Review error logs
- Test with default WordPress theme

### Contact
- **Email**: support@fluxplugins.com
- **Website**: https://fluxplugins.com
- **GitHub**: https://github.com/stratease/flux-media-optimizer

---

**Note**: This plugin is designed for modern WordPress installations with proper server configuration. The architecture is built for scalability, maintainability, and future SaaS integration.
