import React, { lazy, Suspense } from 'react';

import './app.scss';
import Spinner from './Spinner';

const BiIntegration = lazy(() => import('./bi'));

const BIApp = () => {
  return (
    <Suspense fallback={<Spinner />}>
      <BiIntegration />
    </Suspense>
  );
};

export default BIApp;
