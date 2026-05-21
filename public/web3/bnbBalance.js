const { ethers } = require("ethers");

const walletAddress = process.argv[2];

if (!walletAddress) {
    console.log(JSON.stringify({
        success: false,
        message: "Wallet address required"
    }));
    process.exit(1);
}

const provider = new ethers.providers.JsonRpcProvider(
    "https://bsc-dataseed.binance.org"
);

async function main() {
    try {

        const balanceWei = await provider.getBalance(walletAddress);
        const balance = ethers.utils.formatEther(balanceWei);

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