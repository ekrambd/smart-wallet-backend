const { ethers } = require("ethers");

// ======================
// INPUTS
// ======================

const contractAddress1 = process.argv[2];
const contractAddress2 = process.argv[3];

const amount = process.argv[4];

const inputDecimals = process.argv[5];
const outputDecimals = process.argv[6];

// ======================
// CONFIG
// ======================

const provider = new ethers.providers.JsonRpcProvider(
    "https://bsc-dataseed.binance.org/"
);

const pancakeRouterAddress =
    "0x10ED43C718714eb63d5aA57B78B54704E256024E";

// WBNB
const wbnb =
    "0xbb4CdB9CBd36B01bD1cBaEBF2De08d9173bc095c";

// ======================
// ABI
// ======================

const pancakeRouterAbi = [
    "function getAmountsOut(uint amountIn, address[] memory path) public view returns (uint[] memory amounts)"
];

// ======================
// PRICE CHECK
// ======================

async function getPrice() {

    try {

        const pancakeRouter = new ethers.Contract(
            pancakeRouterAddress,
            pancakeRouterAbi,
            provider
        );

        // amount
        const amountIn = ethers.utils.parseUnits(
            amount,
            inputDecimals
        );

        // path
        const path = [
            contractAddress1,
            wbnb,
            contractAddress2
        ];

        // quote
        const amountsOut =
            await pancakeRouter.getAmountsOut(
                amountIn,
                path
            );

        // last token output
        const output =
            ethers.utils.formatUnits(
                amountsOut[2],
                outputDecimals
            );

        console.log(output);

    } catch (error) {

        console.log(error.message);

    }
}

getPrice();