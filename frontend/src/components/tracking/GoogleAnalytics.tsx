import Script from "next/script";

const GoogleAnalytics: React.FC<TGoogleAnalyticsProps> = ({ measurementId }) => {
	return (
		<>
			<Script src={`https://www.googletagmanager.com/gtag/js?id=${measurementId}`} strategy="afterInteractive" />
			<Script id="google-analytics" strategy="afterInteractive">
				{`window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','${measurementId}');`}
			</Script>
		</>
	);
};

type TGoogleAnalyticsProps = {
	measurementId: string;
};

export default GoogleAnalytics;
