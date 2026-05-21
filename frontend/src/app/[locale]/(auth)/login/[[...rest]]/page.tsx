import { SignIn } from "@clerk/nextjs";

const LoginPage: React.FC = () => {
	return (
		<div className="min-h-screen flex items-center justify-center p-4">
			<SignIn appearance={{ layout: { logoLinkUrl: "/" } }} />
		</div>
	);
};

export default LoginPage;
