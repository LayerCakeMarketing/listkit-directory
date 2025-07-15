# ListKit Frontend (Vue 3 SPA)

This is the decoupled Vue 3 SPA frontend for the ListKit application.

## Setup

1. Install dependencies:
```bash
npm install
```

2. Create a `.env` file (optional):
```bash
VITE_API_URL=http://localhost:8000
VITE_APP_NAME=ListKit
```

3. Start the development server:
```bash
npm run dev
```

The frontend will run on http://localhost:5174 with automatic API proxying to Laravel on port 8000.

## Available Scripts

- `npm run dev` - Start development server with hot reload
- `npm run build` - Build for production
- `npm run preview` - Preview production build

## Project Structure

```
frontend/
├── src/
│   ├── assets/         # Static assets and CSS
│   ├── components/     # Reusable Vue components
│   ├── config/         # Configuration files
│   ├── data/           # Static data files
│   ├── router/         # Vue Router configuration
│   ├── stores/         # Pinia stores
│   ├── views/          # Page components
│   ├── App.vue         # Root component
│   └── main.js         # Application entry point
├── public/             # Public static files
├── index.html          # HTML entry point
├── vite.config.js      # Vite configuration
├── tailwind.config.js  # Tailwind CSS configuration
└── package.json        # Dependencies and scripts
```

## Key Features

- **Vue 3 Composition API** - Modern Vue 3 with script setup syntax
- **Vue Router** - Client-side routing with navigation guards
- **Pinia** - State management
- **Tailwind CSS** - Utility-first CSS framework
- **Axios** - HTTP client with automatic CSRF handling
- **Vite** - Fast development and build tool

## API Communication

The frontend communicates with the Laravel backend through:
- RESTful API endpoints under `/api/*`
- Authentication endpoints (`/login`, `/logout`, `/register`)
- Automatic CSRF token handling
- Session-based authentication with cookies

## Authentication Flow

1. User logs in through `/login`
2. Laravel sets session cookies
3. Frontend stores user data in Pinia store
4. Route guards protect authenticated routes
5. Axios automatically includes credentials

## Deployment

For production deployment:

1. Build the frontend:
```bash
npm run build
```

2. The `dist/` folder contains static files to serve
3. Configure your web server to:
   - Serve the frontend files
   - Proxy API requests to Laravel backend
   - Handle client-side routing (redirect to index.html)