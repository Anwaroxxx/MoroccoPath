import type { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path
                d="M12 2C7.9 2 4.5 5.4 4.5 9.5c0 5.6 6.6 11.8 6.9 12.1.3.3.9.3 1.2 0 .3-.3 6.9-6.5 6.9-12.1C19.5 5.4 16.1 2 12 2Zm0 10.5A3 3 0 1 1 12 6.5a3 3 0 0 1 0 6Z"
                fill="currentColor"
            />
        </svg>
    );
}
