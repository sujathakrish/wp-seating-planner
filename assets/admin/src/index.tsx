import React from 'react';
import { createRoot } from 'react-dom/client';
import App from './components/App';
import './styles.css';
const root = createRoot(document.getElementById('sp-root')!);
root.render(<App />);
