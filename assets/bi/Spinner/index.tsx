/*
 * @copyright   Copyright (C) 2022 AesirX. All rights reserved.
 * @license     GNU General Public License version 3, see LICENSE.
 */

import React from 'react';
import MoonLoader from 'react-spinners/MoonLoader';

const Spinner = () => {
  return (
    <div className="loader">
      <MoonLoader color={`#1ab394`} size={`60px`} />
    </div>
  );
};

export default Spinner;
