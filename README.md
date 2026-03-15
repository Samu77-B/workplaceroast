# Workplace Roast Website

A modern, responsive showcase website for Workplace Roast - a hyper-local digital commerce platform connecting neighborhood cafes and businesses with their communities.

## Features

- **Responsive Design**: Fully responsive layout that works on all devices
- **Modern UI**: Clean, professional design using brand colors and typography
- **Interactive Elements**: 
  - Smooth scrolling navigation
  - Testimonial carousel with auto-rotation
  - FAQ accordion
  - Contact form with validation
  - Scroll animations
- **Brand Consistency**: Uses Workplace Roast brand colors and design system

## File Structure

```
website/
├── index.html          # Main HTML structure
├── styles/
│   ├── main.css        # Primary stylesheet with all styles
│   └── responsive.css  # Media queries for responsive design
├── scripts/
│   ├── main.js         # Interactive functionality (navigation, carousel, FAQ, etc.)
│   └── form-handler.js # Contact form validation and submission handling
├── assets/
│   └── images/         # Image assets (logo, etc.)
└── README.md           # This file
```

## Setup Instructions

1. **Clone or download** this repository to your local machine

2. **Open the website**:
   - Simply open `index.html` in a web browser, or
   - Use a local development server:
     ```bash
     # Using Python 3
     python -m http.server 8000
     
     # Using Node.js (if you have http-server installed)
     npx http-server
     ```

3. **View the website**:
   - Navigate to `http://localhost:8000` in your browser

## Customization

### Colors
Brand colors are defined as CSS custom properties in `styles/main.css`:
- `--color-roast-brown`: #4A3728
- `--color-energy-orange`: #D97C2E
- `--color-foam-cream`: #FDF8F2

### Typography
- Headings: Inter (bold)
- Body: Inter (regular)

### Form Submission
The contact form currently simulates submission. To connect to a backend:
1. Update the `handleFormSubmit` function in `scripts/form-handler.js`
2. Replace the setTimeout simulation with an actual API call
3. Configure your backend endpoint

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## Performance

- Optimized CSS and JavaScript
- Smooth animations using CSS transforms
- Lazy loading ready (can be added for images)
- Minimal dependencies (vanilla JavaScript)

## License

© 2024 Workplace Roast. All rights reserved.

