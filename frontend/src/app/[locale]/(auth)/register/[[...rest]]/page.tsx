import { SignUp } from "@clerk/nextjs";

const RegisterPage: React.FC = () => {
	return (
		<div className="min-h-screen flex items-center justify-center p-4">
			<SignUp />
		</div>
	);
};

export default RegisterPage;
