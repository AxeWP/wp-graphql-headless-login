// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore
declare let __webpack_public_path__: string;

declare module '*.svg' {
  import * as React from 'react';

  export const ReactComponent: React.FunctionComponent<
    React.SVGProps<SVGSVGElement> & { title?: string }
  >;

  const src: string;
  export default src;
}
