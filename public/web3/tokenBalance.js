const { ethers } = require("ethers");

const input = JSON.parse(process.argv[2] || "{}");

const provider = new ethers.providers.JsonRpcProvider(
    "https://bsc-dataseed.binance.org/"
);

async function main() {
    try {
        const walletAddress = input.wallet_address;
        const contractAddress = input.contract_address;

        if (!walletAddress || !contractAddress) {
            throw new Error("Missing wallet or contract");
        }

        const abi = [
            "function balanceOf(address) view returns (uint256)",
            "function decimals() view returns (uint8)"
        ];

        const contract = new ethers.Contract(contractAddress, abi, provider);

        const [balanceRaw, decimals] = await Promise.all([
            contract.balanceOf(walletAddress),
            contract.decimals().catch(() => 18) // fallback
        ]);

        const balance = ethers.utils.formatUnits(balanceRaw.toString(), decimals);

        console.log(JSON.stringify({
            success: true,
            balance: balance
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