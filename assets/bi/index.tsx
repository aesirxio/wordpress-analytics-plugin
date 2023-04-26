import React from 'react';
import { createRoot } from 'react-dom/client';
import BIApp from './app';
const container = document.getElementById('biapp');
const root = createRoot(container!);
root.render(<BIApp />);
