const { ethers } = require("ethers");

const input = JSON.parse(process.argv[2] || "{}");

// ✅ Better RPC (IMPORTANT FIX)
const provider = new ethers.providers.JsonRpcProvider(
    "https://rpc.ankr.com/bsc"
);

async function main() {
    try {
        const walletAddress = input.wallet_address;
        const contractAddress = input.contract_address;

        if (!walletAddress || !ethers.utils.isAddress(walletAddress)) {
            throw new Error("Invalid wallet address");
        }

        // =========================
        // NATIVE BNB
        // =========================
        if (!contractAddress) {

            const balance = await provider.getBalance(walletAddress);

            console.log(JSON.stringify({
                success: true,
                type: "bnb",
                balance: ethers.utils.formatEther(balance)
            }));

            return;
        }

        // =========================
        // VALIDATE CONTRACT
        // =========================
        if (!ethers.utils.isAddress(contractAddress)) {
            throw new Error("Invalid contract address");
        }

        // =========================
        // BEP20 TOKEN
        // =========================
        const abi = [
            "function balanceOf(address owner) view returns (uint256)",
            "function decimals() view returns (uint8)"
        ];

        const contract = new ethers.Contract(
            contractAddress,
            abi,
            provider
        );

        const [balance, decimals] = await Promise.all([
            contract.balanceOf(walletAddress),
            contract.decimals()
        ]);

        console.log(JSON.stringify({
            success: true,
            type: "token",
            balance: ethers.utils.formatUnits(balance, decimals)
        }));

    } catch (err) {

        console.log(JSON.stringify({
            success: false,
            balance: "0",
            error: err.message
        }));
    }
}

main();